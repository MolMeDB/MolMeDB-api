
class MissingParameterError(Exception):
    def __init__(self, param:str):
        self.param = param
        super().__init__(f"Missing parameter {param}")