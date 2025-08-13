"use client";
import DetailSection from "../components/section";
import {
  Image,
  Modal,
  ModalContent,
  Spinner,
  Switch,
  useDisclosure,
} from "@heroui/react";
import { Md3dRotation } from "react-icons/md";
import { useState } from "react";
import MolStar from "@/components/providers/molstar";
import IStructure from "@/lib/api/admin/interfaces/Structure";

export default function Compound2D3DStructure(props: { compound: IStructure }) {
  const {
    isOpen: isOpen2D,
    onOpen: onOpen2D,
    onClose: onClose2D,
  } = useDisclosure();
  const [is3Ddisplayed, setIs3Ddisplayed] = useState(false);

  const [isLoading, setIsLoading] = useState(true);

  console.log(props.compound.structure_2d_url);

  return (
    <div className="flex flex-col gap-4">
      <DetailSection
        title="2D/3D Structure"
        order={2}
        endContent={
          <Switch
            isDisabled={!props.compound.structure_3d_url}
            color="primary"
            checked={is3Ddisplayed}
            onChange={(e) => setIs3Ddisplayed(e.target.checked)}
            thumbIcon={({ isSelected, className }) => (
              <Md3dRotation className={className} />
            )}
            size="lg"
          ></Switch>
        }
      >
        {!is3Ddisplayed || !props.compound.structure_3d_url ? (
          <div className="flex flex-row justify-center items-center pt-8 px-8 relative h-[305px]">
            {isLoading && (
              <div className="absolute inset-0 flex items-center justify-center bg-gray/30 z-10">
                <Spinner variant="wave" label="Loading..." />
              </div>
            )}
            <Image
              onClick={onOpen2D}
              onLoad={() => setIsLoading(false)}
              className={`object-cover transition-opacity duration-500 cursor-pointer ${
                isLoading ? "opacity-0" : "opacity-100"
              }`}
              src={props.compound?.structure_2d_url}
              alt="2D Structure"
            />
            <Modal
              size="5xl"
              isOpen={isOpen2D}
              onClose={onClose2D}
              classNames={{
                backdrop: "bg-[#292f46]/50 backdrop-opacity-40",
              }}
            >
              <ModalContent className="flex justify-center items-center p-12">
                {(onClose) => (
                  <Image
                    alt="2D Structure"
                    fetchPriority="low"
                    src={props.compound.structure_2d_url_big}
                  />
                )}
              </ModalContent>
            </Modal>
          </div>
        ) : (
          <div className="flex flex-col justify-center items-center pt-8 px-8 relative h-[305px] w-full ">
            <MolStar sdfPath={props.compound.structure_3d_url} />
          </div>
        )}
      </DetailSection>
    </div>
  );
}
