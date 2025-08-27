import Link from "next/link";

type MenuItem = {
    id: number;
    title: string;
    uri?: string;
    children?: MenuItem[];
    hasContent?: boolean;
};

export default function SectionMenu() {
    const menuItems: MenuItem[] = [
        {
            id: 1,
            title: "About",
            hasContent: true,
            children: [
                {
                    id: 3,
                    title: "Database content",
                    hasContent: true,
                },
            ],
        },
        {
            id: 2,
            title: "Contact",
            hasContent: true,
        },
        {
            id: 4,
            title: "RDF",
            hasContent: true,
            children: [
                {
                    id: 5,
                    title: "Ontological background",
                    hasContent: true,
                },
                {
                    id: 6,
                    title: "RDF data structure",
                    hasContent: true,
                },
                {
                    id: 7,
                    title: "RDF data access",
                    hasContent: true,
                },
                {
                    id: 8,
                    title: "SPARQL endpoint",
                    hasContent: true,
                },
                {
                    id: 9,
                    title: "Acknowledgements",
                    hasContent: true,
                },
                {
                    id: 10,
                    title: "References",
                    hasContent: true,
                },
            ],
        },
        {
            id: 11,
            title: "REST API",
            children: [
                {
                    id: 12,
                    title: "Endpoint - compounds",
                },
                {
                    id: 13,
                    title: "Endpoint - interactions",
                },
                {
                    id: 113,
                    title: "Endpoint - membranes",
                },
                {
                    id: 14,
                    title: "Endpoint - methods",
                },
            ],
        },
    ];

    return (
        <div className="flex flex-col w-full h-full sticky">
            {menuItems.map((item) => (
                <Group
                    key={item.id}
                    title={item.title}
                    children={item.children}
                />
            ))}
        </div>
    );
}

function Group(props: { title: string; children?: MenuItem[] }) {
    return (
        <div className="flex flex-col gap-3 bg-background dark:bg-background-dark-2 border-t-1 border-white dark:border-background-dark p-4">
            <Link
                href={"/docs/about"}
                className="text-md font-semibold hover:text-primary-400 transition-colors duration-400 ease-in-out"
            >
                {props.title}
            </Link>
            {props.children?.map((item) => (
                <Link
                    key={item.id}
                    href={"docs/inner"}
                    className="pl-4 text-sm hover:text-primary-400 dark:hover:text-primary-600 transition-colors duration-400 ease-in-out"
                >
                    {item.title}
                </Link>
            ))}
        </div>
    );
}
