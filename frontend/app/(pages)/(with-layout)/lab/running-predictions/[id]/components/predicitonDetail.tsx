"use client";

import { IPrediction } from "@/lib/api/admin/interfaces/Predictions";
import { Button, DrawerBody, DrawerFooter, DrawerHeader } from "@heroui/react";
import CompoundBasicProperties from "./predictionDetail/basicProperties";
import Compound2DStructure from "./predictionDetail/structure";
import CompoundPredictions from "./predictionDetail/predictions";

export default function PredictionDetail(props: {
  data: IPrediction;
  onClose: () => void;
}) {
  return (
    <>
      <DrawerHeader>Prediction detail</DrawerHeader>
      <DrawerBody>
        <CompoundBasicProperties compound={props.data} />
        <Compound2DStructure compound={props.data} />
        <CompoundPredictions compound={props.data.structure} />
      </DrawerBody>
      <DrawerFooter>
        <Button color="danger" onPress={props.onClose}>
          Close
        </Button>
      </DrawerFooter>
    </>
  );
}
