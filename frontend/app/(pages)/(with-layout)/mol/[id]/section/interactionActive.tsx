import DetailSection from "../components/section";
import IStructure from "@/lib/api/admin/interfaces/Structure";
import ActiveInteractionTable from "../components/tables/activeInteractions";

export default function CompoundPassiveInteractions(props: {
  compound: IStructure;
}) {
  return (
    <DetailSection
      title="Interactions with proteins (active interactions)"
      order={6}
    >
      <>
        <div className="mt-4">
          <ActiveInteractionTable structure={props.compound} />
        </div>
      </>
    </DetailSection>
  );
}
