"use client";

import Link from "next/link";
import SectionContent from "./sections/content";
import SectionMenu from "./sections/menu";
import SectionContents from "./sections/contents";

export default function Client() {
    return (
        <div className="flex w-full min-h-screen flex-row gap-8">
            <div className="w-sm sticky top-20 self-start">
                <SectionMenu />
            </div>
            <div className="w-full flex flex-col gap-4">
                <Breadcrumbs />
                <div className="h-[0.05rem] w-full bg-background dark:bg-foreground/50" />
                <div className="flex flex-row gap-4 w-full h-full">
                    <div className="flex-1">
                        <SectionContent />
                    </div>
                    <div className="w-xs sticky top-20 self-start">
                        <SectionContents />
                    </div>
                </div>
            </div>
        </div>
    );
}

function Breadcrumbs() {
    const items = [
        {
            label: "RDF",
            link: "/docs/rdf",
        },
        {
            label: "Ontological background",
        },
    ];

    return (
        <div className="flex flex-row gap-3 text-lg">
            {items.map((item, index) =>
                item.link ? (
                    <div key={index} className="flex flex-row gap-3">
                        <Link
                            href={item.link}
                            className="text-primary hover:underline"
                        >
                            {item.label}
                        </Link>
                        <span className="text-zinc-600 dark:text-foreground/80">
                            /
                        </span>
                    </div>
                ) : (
                    <label
                        key={index}
                        className="text-zinc-600 dark:text-foreground/80"
                    >
                        {item.label}
                    </label>
                )
            )}
        </div>
    );
}
