
class InvalidParameterError(Exception):
    def __init__(self, param:str):
        self.param = param
        super().__init__(f"Invalid value of parameter {param}")