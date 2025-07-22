<?php
namespace App\ValueObjects;

use App\Enums\UploadQueueLogContextEnums;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;

class UploadQueueLog extends Model implements JsonSerializable
{
    public function __construct(
        public string $message, 
        UploadQueueLogContextEnums | string $context, 
        public ?string $timestamp = null, 
        public ?string $user_id = null)
    {
        $this->context = is_string($context) ? UploadQueueLogContextEnums::from($context) : $context;
        $this->message = $message;
        $this->timestamp = $timestamp;
        $this->user_id = $user_id;
    }

    public function jsonSerialize(): array
    {
        return [
            'message' => $this->message,
            'context' => $this->context->value,
            'timestamp' => $this->timestamp,
            'user_id' => $this->user_id
        ];
    }

    public static function remapOldLogs($data) : array
    {
        if(self::hasValidListStructure($data))
            return $data;

        $result = [];

        if(isset($data['error']))
        {
            $result += array_map(function ($msg) {
                return new UploadQueueLog($msg, UploadQueueLogContextEnums::ERROR);
            }, $data['error']);

            unset($data['error']);
        }

        if(isset($data['warning']))
        {
            $result += array_map(function ($msg) {
                return new UploadQueueLog($msg, UploadQueueLogContextEnums::WARNING);
            }, $data['warning']);

            unset($data['warning']);
        }

        if(isset($data['success']))
        {
            $result += array_map(function ($msg) {
                return new UploadQueueLog($msg, UploadQueueLogContextEnums::SUCCESS);
            }, $data['success']);

            unset($data['success']);
        }

        foreach($data as $obj) 
        {
            if(is_object($obj) && isset($obj->message) && isset($obj->context))
            {
                $result[] = new UploadQueueLog(
                    $obj->message, 
                    UploadQueueLogContextEnums::from($obj->context), 
                    isset($obj->timestamp) ? $obj->timestamp : null,
                    isset($obj->user_id) ? $obj->user_id : null
                );
            }
        }
        return $result;
    }

    public static function hasValidListStructure($data) : bool
    {
        if (!is_array($data)) return false;
        else if (!count($data)) return true;
        
        if(!isset($data[0]) || 
            (is_array($data[0]) && (!isset($data[0]['message']) || !isset($data[0]['context']) || !isset($data[0]['timestamp']))) || 
            (is_object($data[0]) && (!isset($data[0]->message) || !isset($data[0]->context) || !isset($data[0]->timestamp))))
            return false;

        return true;
    }
}