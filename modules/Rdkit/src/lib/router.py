from lib.exceptions.InvalidRoute import InvalidPathError
import services.RDKIT as R
from lib.endpoint import Endpoint, ApiResponse, ResponseType

class bcolors:
    GREEN = '\033[92m'
    RED = '\033[91m'
    RESET = '\033[0m'
    
class Router:
    def __init__(self):
        self.routes = {}
        self.tests = {}
        
    def add(self, path, handler, required=None, optional=None):
        self.routes[path] = Endpoint(handler, required, optional)
        
    def resolve(self, path, params) -> ApiResponse:
        endpoint = self.routes.get(path)
        if not endpoint:
            raise InvalidPathError(path)
        return endpoint.call(params)
    
    def test(self, params = {}) -> ApiResponse:
        return ApiResponse(200, "OK", ResponseType.TEXT)
    
    def registerRoutes(self):
        ##############################
        ##### Test endpoint
        self.add(path='test', handler=self.test)
        ##### Canonize SMILES
        self.add(path='structure/canonize', handler=R.RDKIT().canonizeSmiles, required=['smi'])
        # - tests
        self.tests['structure/canonize'] = [
            {
                'input': {'smi': 'CCCOC1=CC=CC=C1'},
                'expected': 'CCCOc1ccccc1'
            },
            {
                'input': {'smi': 'C1=CC=CC=C1C'},
                'expected': 'Cc1ccccc1'
            },
        ]
        ##############################
        ##### Neutralize SMILES
        self.add(path='structure/neutralize', handler=R.RDKIT().neutralizeSmiles, required=['smi'])
        # - tests
        self.tests['structure/neutralize'] = [
            {
                'input': {'smi': 'CC(C)Cc1ccc([C@H](C)C(=O)[O-])cc1'},
                'expected': 'CC(C)Cc1ccc([C@H](C)C(=O)O)cc1'
            },
        ]
        ##############################
        #### Get molecule representat
        self.add(path='structure/representant', handler=R.RDKIT().representantSmiles, required=['smi'])
        # - tests
        self.tests['structure/representant'] = [
            {
                'input': {'smi': 'CC(C)Cc1ccc([C@H](C)C(=O)[O-])cc1'},
                'expected': 'CC(C)Cc1ccc(C(C)C(=O)O)cc1'
            },
        ]
        ##############################
        ##############################
        #### Get molecule 3D structure
        self.add(path='structure/3d', handler=R.RDKIT().make3Dstructure, required=['smi'])
        # - tests
        self.tests['structure/3d'] = [
            {
                'input': {'smi': 'CC(C)Cc1ccc([C@H](C)C(=O)[O-])cc1'},
                'expected': 'file'
            },
        ]
        ##############################
        ##############################
        #### Get structure inchikey
        self.add(path='structure/inchikey', handler=R.RDKIT().makeInchi, required=['smi'])
        # - tests
        self.tests['structure/inchikey'] = [
            {
                'input': {'smi': 'CC(C)Cc1ccc([C@H](C)C(=O)[O-])cc1'},
                'expected': 'HEFNNWSXXWATRW-JTQLQIEISA-M'
            },
        ]
        ##############################
        ##############################
        #### Get structure general info
        self.add(path='structure/info', handler=R.RDKIT().getGeneralInfo, required=['smi'])
        # - tests
        self.tests['structure/info'] = [
            {
                'input': {'smi': 'CC(C)Cc1ccc([C@H](C)C(=O)[O-])cc1'},
                'expected': 'object'
            },
        ]
        ##############################
        ##############################
        #### Get structure fingerprint
        self.add(path='structure/fingerprint', handler=R.RDKIT().getFingerprint, required=['smi'])
        # - tests
        self.tests['structure/fingerprint'] = [
            {
                'input': {'smi': 'CC(C)Cc1ccc([C@H](C)C(=O)[O-])cc1'},
                'expected': 'object'
            },
        ]
        ##############################
        #### Get structure fingerprint
        self.add(path='structure/formalCharge', handler=R.RDKIT().formalCharge, required=['smi'])
        # - tests
        self.tests['structure/formalCharge'] = [
            {
                'input': {'smi': 'CC(C)Cc1ccc([C@H](C)C(=O)[O-])cc1'},
                'expected': '-1'
            },
        ]
        ##############################
        
    def runTests(self):
        print("=== Running endpoint tests ===")
        passed = True
        for path, test_cases in self.tests.items():
            for idx, test in enumerate(test_cases, 1):
                try:
                    result = self.resolve(path, test['input'])
                    output = result.data
                    
                    if result.type == ResponseType.TEXT:
                        if str(output) == str(test['expected']):
                            print(f"{path} test #{idx}: {bcolors.GREEN}PASS{bcolors.RESET}")
                        else: 
                            passed = False
                            print(f"{path} test #{idx}: {bcolors.RED}FAIL{bcolors.RESET} - expected {test['expected']}, got {output}")
                    elif result.type == ResponseType.FILECONTENT:
                        if test["expected"] == 'file' and len(str(output)) > 0:
                            print(f"{path} test #{idx}: {bcolors.GREEN}PASS{bcolors.RESET}")
                        else:
                            passed = False
                            print(f"{path} test #{idx}: {bcolors.RED}FAIL{bcolors.RESET} - expected {test['expected']}, got {output}")
                    elif result.type == ResponseType.JSON:
                        if test["expected"] == 'object' and isinstance(output, dict):
                            print(f"{path} test #{idx}: {bcolors.GREEN}PASS{bcolors.RESET}")
                        else:
                            passed = False
                            print(f"{path} test #{idx}: {bcolors.RED}FAIL{bcolors.RESET} - expected {test['expected']}, got {output}")        
                    else:
                        passed = False
                        print(f"{path} test #{idx}: {bcolors.RED}FAIL{bcolors.RESET} - expected {test['expected']}, got {output}")
                            
                except Exception as e:
                    passed = False
                    print(f"{path} test #{idx}: {bcolors.RED}FAIL{bcolors.RESET} - Exception: {e}")
        return passed