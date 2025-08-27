from dataclasses import dataclass
from enum import Enum
from lib.exceptions.MissingParameter import MissingParameterError

class Endpoint:
    def __init__(self, handler, required=None, optional=None):
        self.handler = handler
        self.required = required or []
        self.optional = optional or []
        
    def call(self, params):
        for r in self.required:
            if r not in params:
                raise MissingParameterError(r)
        return self.handler(params)
    

class ResponseType(Enum):
    TEXT="text"
    JSON="json"
    FILECONTENT="file"
    
@dataclass
class ApiResponse:
    code: int
    data: dict
    type: ResponseType = ResponseType.TEXT