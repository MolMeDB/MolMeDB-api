"use client";

type Item = {
  title: string;
  id: string;
  children?: Item[];
};

const data: Item[] = [
  {
    title: "Substances",
    id: "subst",
    children: [
      {
        title: "Substance attributes",
        id: "subst-attr",
      },
    ],
  },
  {
    title: "Membrane interactions",
    id: "membr",
    children: [
      {
        title: "Membrane interaction measure groups",
        id: "membr-attr-mg",
      },
      {
        title: "Experimental conditions",
        id: "membr-attr-ec",
      },
      {
        title: "Membrane interaction endpoints",
        id: "membr-attr-end",
      },
      {
        title: "Membranes and methods",
        id: "membr-attr-membr-method",
      },
    ],
  },
  {
    title: "Transporter interactions",
    id: "transp",
    children: [
      {
        title: "Transporter interaction measure groups",
        id: "transp-attr-mg",
      },
      {
        title: "Transporter interaction endpoints",
        id: "transp-attr-end",
      },
      {
        title: "Transporters",
        id: "transp-attr-transp-method",
      },
    ],
  },
  {
    title: "References",
    id: "ref",
  },
];

export default function SectionContents(props: {}) {
  return (
    <div className="flex flex-col gap-8  p-4 pl-6 mt-4 border-l-2 border-secondary/20">
      <h1 className="text-2xl font-bold text-foreground/80">Contents</h1>
      <div className="flex flex-col gap-2">
        {data.map((item) => (
          <Item key={item.id} detail={item} />
        ))}
      </div>
    </div>
  );
}

function Item(props: { detail: Item }) {
  return (
    <div className="flex flex-col gap-2">
      <h3>{props.detail.title}</h3>
      <div className="flex flex-col gap-2 text-sm pl-4">
        {props.detail.children?.map((item) => (
          <Item key={item.id} detail={item} />
        ))}
      </div>
    </div>
  );
}
