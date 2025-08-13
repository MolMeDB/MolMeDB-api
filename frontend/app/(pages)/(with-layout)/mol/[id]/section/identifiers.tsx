"use client";
import DetailSection from "../components/section";
import DetailProperty from "../components/property";
import { TbLetterI, TbLetterS } from "react-icons/tb";
import Image from "next/image";
import { MdCheckCircleOutline } from "react-icons/md";
import IStructure, {
  IIdentifierType,
} from "@/lib/api/admin/interfaces/Structure";
import { IdentifierSourceTooltip } from "./basicProperties";

export default function CompoundIdentifiers(props: { compound: IStructure }) {
  const chebi = props.compound.identifiers
    ?.filter((i) => i.type == IIdentifierType.CHEBI)
    .pop();
  const chembl = props.compound.identifiers
    ?.filter((i) => i.type == IIdentifierType.CHEMBL)
    .pop();
  const pubchem = props.compound.identifiers
    ?.filter((i) => i.type == IIdentifierType.PUBCHEM)
    .pop();
  const pdb = props.compound.identifiers
    ?.filter((i) => i.type == IIdentifierType.PDB)
    .pop();
  const drugbank = props.compound.identifiers
    ?.filter((i) => i.type == IIdentifierType.DRUGBANK)
    .pop();

  return (
    <DetailSection title="Identifiers" order={3}>
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 gap-y-6">
        <DetailProperty
          icon={<TbLetterS size={30} />}
          title="SMILES"
          value={props.compound.canonical_smiles ?? "N/A"}
          className="col-span-2 lg:col-span-4"
          isValid
          tooltipContent={
            <div className="flex flex-col">
              <div className="flex flex-row justify-start text-xs">
                RdKit software
              </div>
              <div className="flex flex-row items-center gap-1">
                <MdCheckCircleOutline className="text-success" />
                Canonized SMILES
              </div>
            </div>
          }
        />
        <DetailProperty
          icon={<TbLetterI size={30} />}
          title="InchiKey"
          value={props.compound.inchikey ?? "N/A"}
          className="col-span-2"
          isValid
          tooltipContent={
            <div className="flex flex-col">
              <div className="flex flex-row justify-start text-xs">
                RdKit software
              </div>
              <div className="flex flex-row items-center gap-1">
                <MdCheckCircleOutline className="text-success" />
                Calculated from <b>SMILES</b>
              </div>
            </div>
          }
        />
        <DetailProperty
          icon={
            <IdentifierSourceTooltip item={chebi?.source}>
              <Image
                src="/assets/partners/small/chebi.png"
                alt="ChEBI logo"
                width={30}
                height={30}
                className="w-8 h-auto"
              />
            </IdentifierSourceTooltip>
          }
          title="ChEBI"
          value={chebi?.value ?? "N/A"}
        />
        <DetailProperty
          icon={
            <IdentifierSourceTooltip item={chembl?.source}>
              <Image
                src="/assets/partners/small/chembl-small.png"
                alt="ChEMBL logo"
                width={30}
                height={30}
                className="w-8 h-auto"
              />
            </IdentifierSourceTooltip>
          }
          title="ChEMBL"
          value={chembl?.value ?? "N/A"}
        />
        <DetailProperty
          icon={
            <IdentifierSourceTooltip item={pdb?.source}>
              <Image
                src="/assets/partners/small/pdb-small.png"
                alt="PDB logo"
                width={30}
                height={30}
                className="w-8 h-auto"
              />
            </IdentifierSourceTooltip>
          }
          title="PDB"
          value={pdb?.value ?? "N/A"}
        />
        <DetailProperty
          icon={
            <IdentifierSourceTooltip item={pubchem?.source}>
              <Image
                src="/assets/partners/small/pubchem-small.png"
                alt="PubChem logo"
                width={30}
                height={30}
                className="w-8 h-auto"
              />
            </IdentifierSourceTooltip>
          }
          title="Pubchem"
          value={pubchem?.value ?? "N/A"}
        />
        <DetailProperty
          icon={
            <IdentifierSourceTooltip item={drugbank?.source}>
              <Image
                src="/assets/partners/small/drugbank-pill.png"
                alt="Drugbank logo"
                width={30}
                height={30}
                className="w-8 h-auto"
              />
            </IdentifierSourceTooltip>
          }
          title="Drugbank"
          value={drugbank?.value ?? "N/A"}
        />
        {/* <DetailProperty
          icon={
            <Image
              src="/assets/partners/small/drugbank-pill.png"
              alt="Drugbank logo"
              width={30}
              height={30}
              className="w-8 h-auto"
            />
          }
          title="Drugbank"
          value={props.compound.identifiers?.drugbank ?? "N/A"}
          isValid={false}
          tooltipContent={
            <div className="flex flex-col">
              <div className="flex flex-row justify-start text-xs">
                Not validated
              </div>
              <div className="flex flex-row items-center gap-1">
                <MdOutlineQuestionMark className="text-warning" />
                Automatically loaded using <b>PubChem ID</b>
              </div>
            </div>
          }
        />
        <DetailProperty
          icon={
            <Image
              src="/assets/partners/small/pubchem-small.png"
              alt="Pubchem logo"
              width={30}
              height={30}
              className="w-8 h-auto"
            />
          }
          title="Pubchem"
          value={props.compound.identifiers?.pubchem ?? "N/A"}
        />
        <DetailProperty
          icon={
            <Image
              src="/assets/partners/small/chembl-small.png"
              alt="ChEMBL logo"
              width={30}
              height={30}
              className="w-8 h-auto"
            />
          }
          title="ChEMBL"
          value={props.compound.identifiers?.chembl ?? "N/A"}
        />
        <DetailProperty
          icon={
            <Image
              src="/assets/partners/small/pdb-small.png"
              alt="PDB logo"
              width={30}
              height={30}
              className="w-8 h-auto"
            />
          }
          title="PDB"
          value={props.compound.identifiers?.pdb ?? "N/A"}
        /> */}
      </div>
    </DetailSection>
  );
}
