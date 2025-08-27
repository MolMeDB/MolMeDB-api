from rdkit import Chem, DataStructs
from rdkit.Chem import Descriptors
from rdkit.Chem import Crippen
from rdkit.Chem import rdDepictor as Depict
from rdkit.Chem import AllChem, Draw
from lib.endpoint import ApiResponse, ResponseType
import services.mmpa.rfrag as rfrag
from lib.exceptions.InvalidParameter import InvalidParameterError
import services.gen_confomers as cnf

class RDKIT:
    # Fragment molecule
    # def mmpaFragment(self, params = {}, parent = None):
    #     if "mol" not in params.keys():
    #         return None

    #     smiles = params["mol"]
    #     id = params["id"] if "id" in params.keys() else 1
    #     print_result = True if "asHTML" in params.keys() else False

    #     # First, canonize smiles
    #     canonized_smiles = Chem.MolToSmiles(Chem.MolFromSmiles(smiles))

    #     if not canonized_smiles:
    #         return None

    #     fragments = rfrag.fragment_mol(canonized_smiles, id)

    #     result = [] 

    #     for i in fragments:
    #         t = i.split(",")
    #         if(len(t) < 4):
    #             continue
    #         f = t[3].split(".")
    #         f_res = []
    #         for ft in f:
    #             f_res.append({
    #                 "smiles": ft,
    #                 "similarity": self.similarity(canonized_smiles, ft)
    #             })
            
    #         if(t[2]):
    #             f_res.append({
    #                 "smiles": t[2],
    #                 "similarity": self.similarity(canonized_smiles, t[2])
    #             })

    #         result.append({
    #             "input": t[0],
    #             "identifier": t[1],
    #             "fragments": f_res,
    #         })

    #     return True, result, print_result

    # # Returns mol fingerprints
    def getFingerprint(self, params = {}):
        s = params["smi"]
        mol = Chem.MolFromSmiles(s)
        
        if mol is None:
            raise InvalidParameterError("smi")
        
        fp = Chem.RDKFingerprint(mol)

        return ApiResponse(200, {
            "smiles": s,
            "fingerprint": fp.ToBitString()
        }, ResponseType.JSON)

    # def similarity(self, smiles1, smiles2):
    #     if not smiles1 or not smiles2:
    #         return None

    #     smiles1 = re.sub(r'\[\*:[0-9]*\]', "[H]", smiles1)
    #     smiles2 = re.sub(r'\[\*:[0-9]*\]', "[H]", smiles2)

    #     mol1 = Chem.MolFromSmiles(smiles1)
    #     mol2 = Chem.MolFromSmiles(smiles2)

    #     fp1 = Chem.RDKFingerprint(mol1)
    #     fp2 = Chem.RDKFingerprint(mol2)

    #     return {
    #         "Tanimoto": DataStructs.FingerprintSimilarity(fp1,fp2, metric=DataStructs.TanimotoSimilarity),
    #         "Dice": DataStructs.FingerprintSimilarity(fp1,fp2, metric=DataStructs.DiceSimilarity),
    #         "Cosine": DataStructs.FingerprintSimilarity(fp1,fp2, metric=DataStructs.CosineSimilarity),
    #         "Sokal": DataStructs.FingerprintSimilarity(fp1,fp2, metric=DataStructs.SokalSimilarity),
    #         "Russel": DataStructs.FingerprintSimilarity(fp1,fp2, metric=DataStructs.RusselSimilarity),
    #         "Kulczynski": DataStructs.FingerprintSimilarity(fp1,fp2, metric=DataStructs.KulczynskiSimilarity),
    #         "McConnaughey": DataStructs.FingerprintSimilarity(fp1,fp2, metric=DataStructs.McConnaugheySimilarity),
    #     }


    # # Compute tanimoto betwween molecules
    # def computeSimilarity(self, params = {}):
    #     if "smi1" not in params.keys() or "smi2" not in params.keys():
    #         return None

    #     smiles1 = params["smi1"]
    #     smiles2 = params["smi2"]

    #     return True, {
    #         "smiles1": smiles1,
    #         "smiles2": smiles2,
    #         "similarity": self.similarity(smiles1, smiles2)
    #     }


    # Canonize SMILES
    def canonizeSmiles(self, params = {}):
        req_smiles = params["smi"]
        mol = Chem.MolFromSmiles(req_smiles)
        if mol is None:
            raise InvalidParameterError("smi")
        return ApiResponse(200, Chem.MolToSmiles(mol))

    # get charge of structure
    def formalCharge(self, params = {}):
        req_smiles = params["smi"]
        mol = Chem.MolFromSmiles(req_smiles)
        if mol is None:
            raise InvalidParameterError("smi")
        return ApiResponse(200, Chem.GetFormalCharge(mol))
    
    # Neutralize SMILES as possible
    def neutralizeSmiles(self, params = {}):
        req_smiles = params["smi"]
        mol = Chem.MolFromSmiles(req_smiles)
        if mol is None:
            raise InvalidParameterError("smi")
        return ApiResponse(200, Chem.MolToSmiles(self._neutralize_atoms(mol)))
    
    def _neutralize_atoms(self, mol):
        pattern = Chem.MolFromSmarts("[+1!h0!$([*]~[-1,-2,-3,-4]),-1!$([*]~[+1,+2,+3,+4])]")
        at_matches = mol.GetSubstructMatches(pattern)
        at_matches_list = [y[0] for y in at_matches]
        if len(at_matches_list) > 0:
            for at_idx in at_matches_list:
                atom = mol.GetAtomWithIdx(at_idx)
                chg = atom.GetFormalCharge()
                hcount = atom.GetTotalNumHs()
                atom.SetFormalCharge(0)
                atom.SetNumExplicitHs(hcount - chg)
                atom.UpdatePropertyCache()
        return mol

    # Find representant for molecule
    def representantSmiles(self, params = {}):
        req_smiles = params["smi"]
        mol = Chem.MolFromSmiles(req_smiles)
        if mol is None:
            raise InvalidParameterError("smi")
        Chem.RemoveStereochemistry(mol)
        return ApiResponse(200, Chem.MolToSmiles(self._neutralize_atoms(mol)))

    # # Makes 3D structure
    def make3Dstructure(self, params = {}):
        req_smiles = params["smi"]
        mol = Chem.MolFromSmiles(req_smiles)
        
        if mol is None:
            raise InvalidParameterError("smi")

        m3 = Chem.AddHs(mol)
        AllChem.EmbedMolecule(m3, AllChem.ETKDG())
        
        # Try optimize structure
        AllChem.MMFFOptimizeMolecule(m3)
        
        # Get 3D structure
        structure = Chem.MolToMolBlock(m3)

        return ApiResponse(200, structure, ResponseType.FILECONTENT)

    # # Generate InChIKey
    def makeInchi(self, params = {}):
        req_smiles = params["smi"]
        mol = Chem.MolFromSmiles(req_smiles)
        
        if mol is None:
            raise InvalidParameterError("smi")
        
        inchi = Chem.inchi.MolToInchiKey(mol)
        return ApiResponse(200, inchi)

    # # Compute LogP
    def getGeneralInfo(self, params = {}):
        req_smiles = params["smi"]
        mol = Chem.MolFromSmiles(req_smiles)
        
        if mol is None:
                raise InvalidParameterError("smi")

        inchi = Chem.inchi.MolToInchi(mol)

        return ApiResponse(200, {
            "canonized_smiles": Chem.MolToSmiles(mol),
            "inchi": inchi,
            'inchikey': Chem.inchi.InchiToInchiKey(inchi),
            "MW": round(Descriptors.MolWt(mol), 2),
            "LogP": round(Crippen.MolLogP(mol), 2)
        }, ResponseType.JSON)

    # # Returns all charge states for given molecule smiles
    # def getAllChargeSmiles(self, params = {}):
    #     if params is None or "smi" not in params or params["smi"] is None:
    #         print("Missing SMI parameter")
    #         return []
        
    #     if "limit" not in params:
    #         limit = 20
    #     else:
    #         limit = int(params["limit"])

    #     smiles = params["smi"]
    #     # Check smiles validity
    #     mol = Chem.MolFromSmiles(smiles)

    #     if mol is None:
    #         print("Invalid SMILES:", smiles)
    #         return []

    #     # Add library
    #     from dimorphite_dl import DimorphiteDL

    #     phLimits = [
    #         (0,14),
    #         (1,13),
    #         (2,11),
    #         (3,10),
    #         (4,9),
    #         (5,8),
    #         (6,8),
    #         (6.8,7.5),
    #         (7,7.5)
    #     ]

    #     for start, end in phLimits:
    #         dimorphite_dl = DimorphiteDL(
    #             min_ph=start, # Whole pH range
    #             max_ph=end,
    #             max_variants=128,
    #             label_states=False,
    #             pka_precision=1.0
    #         )

    #         # Get all protonated/deprotonated states
    #         prot_smiles_list = dimorphite_dl.protonate(smiles)

    #         if len(prot_smiles_list) < limit:
    #             break

    #     return {"pH_start": start, "pH_end": end, "molecules": prot_smiles_list}

        
    # # Returns COSMO conformers
    # def COSMO_conformers(self, params = {}):
    #     if params is None or "smi" not in params or params["smi"] is None:
    #         print("Missing `smi` parameter.")
    #         return []

    #     if "name" not in params or not params["name"]:
    #         print("Invalid structure name.")
    #         return []

    #     smiles = params["smi"]
    #     name = params["name"]
    #     # Check smiles validity
    #     mol = Chem.MolFromSmiles(smiles)

    #     if mol is None:
    #         print("Invalid SMILES:", smiles)
    #         return []

    #     instance = cnf.Conformers()
    #     return instance.run(mol, name)

        

        