import { MdInfoOutline } from "react-icons/md";

export default function InfoBox(props: {
  icon: React.ReactNode;
  title: string;
  help?: React.ReactNode;
  children: React.ReactNode;
}) {
  return (
    <div className="w-full py-4 px-8 flex flex-col gap-4 border-1 border-zinc-200 rounded-xl shadow-lg bg-white">
      <div className="flex flex-row justify-between gap-8 items-center text-zinc-700">
        <div className="flex flex-row items-center gap-3">
          {props.icon}
          <div className="text-xl font-semibold">{props.title}</div>
        </div>
        {props.help && (
          <div>
            <MdInfoOutline size={24} className="" />
          </div>
        )}
      </div>
      <div>{props.children}</div>
    </div>
  );
}
