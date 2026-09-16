import unittest

from services.RDKIT import RDKIT


class PredictionSmilesValidationTest(unittest.TestCase):
    def setUp(self):
        self.rdkit = RDKIT()

    def test_counts_implicit_hydrogens(self):
        response = self.rdkit.validatePredictionSmiles({"smi": "CCO"})

        self.assertEqual(200, response.code)
        self.assertTrue(response.data["valid"])
        self.assertEqual(9, response.data["atom_count"])
        self.assertEqual({"C": 2, "H": 6, "O": 1}, response.data["element_counts"])

    def test_rejects_more_than_120_atoms_including_hydrogens(self):
        response = self.rdkit.validatePredictionSmiles({"smi": "C" * 41})

        self.assertEqual(422, response.code)
        self.assertFalse(response.data["valid"])
        self.assertEqual(125, response.data["atom_count"])
        self.assertIn("the maximum is 120", response.data["errors"][0])

    def test_accepts_exactly_120_atoms_including_hydrogens(self):
        cyclotetracontane = "C1" + ("C" * 38) + "C1"
        response = self.rdkit.validatePredictionSmiles({"smi": cyclotetracontane})

        self.assertEqual(200, response.code)
        self.assertTrue(response.data["valid"])
        self.assertEqual(120, response.data["atom_count"])

    def test_rejects_disconnected_structures(self):
        response = self.rdkit.validatePredictionSmiles({"smi": "CCO.[Na+]"})

        self.assertEqual(422, response.code)
        self.assertEqual(2, response.data["fragment_count"])
        self.assertIn("Na", response.data["disallowed_elements"])

    def test_rejects_unsupported_elements(self):
        response = self.rdkit.validatePredictionSmiles({"smi": "C[Si](C)C"})

        self.assertEqual(422, response.code)
        self.assertEqual(["Si"], response.data["disallowed_elements"])

    def test_accepts_supported_halogens(self):
        response = self.rdkit.validatePredictionSmiles({"smi": "FC(Cl)(Br)I"})

        self.assertEqual(200, response.code)
        self.assertTrue(response.data["valid"])


if __name__ == "__main__":
    unittest.main()
