"use client";

import IStructure, {
  ISimmilarStructure,
} from "@/lib/api/admin/interfaces/Structure";
import DetailSection from "../components/section";
import { Card, CardBody, CardHeader, Image } from "@heroui/react";

export default function CompoundSimilarEntries(props: {
  compound: IStructure;
}) {
  const entries: ISimmilarStructure[] = [];

  return (
    <DetailSection title="Similar entries" order={4}>
      <div className="flex flex-row gap-4 overflow-x-scroll scroll whitespace-nowrap p-6 pb-12">
        {entries.length ? (
          entries.map((entry) => <Record compound={entry} />)
        ) : (
          <div className="h-24 w-full flex flex-col justify-center items-center">
            <label className="text-xl font-bold text-foreground-400 select-none">
              No similar entries found...
            </label>
          </div>
        )}
      </div>
    </DetailSection>
  );
}

function Record(props: { compound: ISimmilarStructure }) {
  return (
    <a href={`/mol/${props.compound.identifier}`}>
      <Card className="p-4 min-w-64 max-w-64 h-[350px] cursor-pointer">
        <CardHeader className="w-full h-32">
          <Image
            src={props.compound?.structure_2d_url}
            alt="Molecule 2D structure"
          />
        </CardHeader>
        <CardBody className="flex flex-col">
          <div className="flex flex-row justify-center">
            <h3 className="text-lg font-semibold text-foreground/80 line-clamp-1">
              {props.compound?.name ?? props.compound.identifier}
            </h3>
          </div>
          <div className="flex flex-col gap-1 mt-4 text-sm text-foreground/70">
            <div className="flex flex-row items-center border-b-1 border-foreground/30">
              <div className="w-1/2">Similarity</div>
              <div className="w-1/2 text-right">
                {props.compound.similarity.tanimoto}
              </div>
            </div>
            <div className="flex flex-row items-center border-b-1 border-foreground/30">
              <div className="w-1/2">Molecular weight</div>
              <div className="w-1/2 text-right">
                {props.compound.molecular_weight ?? "N/A"}
              </div>
            </div>
            <div className="flex flex-row items-center border-b-1 border-foreground/30">
              <div className="w-1/2">LogP</div>
              <div className="w-1/2 text-right">
                {props.compound.logp ?? "N/A"}
              </div>
            </div>
            <div className="flex flex-row items-center border-b-1 border-foreground/30">
              <div className="w-1/2"># passive int.</div>
              <div className="w-1/2 text-right">
                {props.compound.total.interactions_passive ?? "N/A"}
              </div>
            </div>
            <div className="flex flex-row items-center border-b-1 border-foreground/30">
              <div className="w-1/2"># active int.</div>
              <div className="w-1/2 text-right">
                {props.compound.total.interactions_active ?? "N/A"}
              </div>
            </div>
          </div>
        </CardBody>
      </Card>
    </a>
  );
}
