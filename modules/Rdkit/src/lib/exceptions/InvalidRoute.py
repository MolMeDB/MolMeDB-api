
class InvalidPathError(Exception):
    def __init__(self, path:str):
        self.path = path
        super().__init__(f"Invalid path {path}")