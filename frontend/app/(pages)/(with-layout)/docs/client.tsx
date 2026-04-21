"use client";

import { Spinner } from "@heroui/react";
import Link from "next/link";
import { useEffect, useMemo, useState } from "react";

type DocumentArticleTreeNode = {
  id: number;
  title: string;
  slug: string;
  path: string;
  children: DocumentArticleTreeNode[];
};

type DocumentArticleBreadcrumb = {
  title: string;
  path: string;
};

type DocumentArticle = {
  id: number;
  title: string;
  slug: string;
  path: string;
  content: string;
  breadcrumbs: DocumentArticleBreadcrumb[];
};

type Heading = {
  id: string;
  title: string;
  level: 1 | 2;
};

type ClientProps = {
  initialSlug: string[];
};

type ParsedArticleContent = {
  html: string;
  headings: Heading[];
};

export default function Client(props: ClientProps) {
  const [tree, setTree] = useState<DocumentArticleTreeNode[]>([]);
  const [article, setArticle] = useState<DocumentArticle | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const slugPath = useMemo(
    () => props.initialSlug.join("/"),
    [props.initialSlug],
  );
  const parsedContent = useMemo<ParsedArticleContent>(() => {
    return parseContent(article?.content ?? "");
  }, [article?.content]);

  useEffect(() => {
    const abortController = new AbortController();

    async function loadData() {
      setLoading(true);
      setError(null);

      try {
        const [treeResponse, articleResponse] = await Promise.all([
          fetch("/api/docs/tree", { signal: abortController.signal }),
          fetch(
            slugPath.trim().length > 0
              ? `/api/docs/article/${slugPath}`
              : "/api/docs/article",
            {
              signal: abortController.signal,
            },
          ),
        ]);

        if (!treeResponse.ok) {
          const treeError = (await treeResponse.json().catch(() => null)) as {
            message?: string;
          } | null;
          throw new Error(
            treeError?.message ?? "Failed to load documentation menu.",
          );
        }

        if (!articleResponse.ok) {
          const articleError = (await articleResponse
            .json()
            .catch(() => null)) as {
            message?: string;
          } | null;
          throw new Error(
            articleError?.message ?? "Failed to load documentation article.",
          );
        }

        const treeJson = (await treeResponse.json()) as {
          data?: DocumentArticleTreeNode[];
        };
        const articleJson = (await articleResponse.json()) as {
          data?: DocumentArticle;
        };

        setTree(treeJson.data ?? []);
        setArticle(articleJson.data ?? null);
      } catch (err) {
        if (abortController.signal.aborted) {
          return;
        }

        setTree([]);
        setArticle(null);
        setError(
          err instanceof Error
            ? err.message
            : "Documentation cannot be loaded right now.",
        );
      } finally {
        if (!abortController.signal.aborted) {
          setLoading(false);
        }
      }
    }

    loadData();

    return () => {
      abortController.abort();
    };
  }, [slugPath]);

  if (loading) {
    return (
      <div className="flex min-h-[55vh] flex-col items-center justify-center text-default-600">
        <Spinner size="lg" color="primary" className="mb-4" />
        Loading content...
      </div>
    );
  }

  if (error) {
    return (
      <div className="rounded-xl border border-danger-200 bg-danger-50 p-4 text-danger-700">
        {error}
      </div>
    );
  }

  if (!article) {
    return (
      <div className="rounded-xl border border-default-200 bg-default-50 p-4 text-default-700">
        No published documentation article found.
      </div>
    );
  }

  return (
    <div className="flex w-full min-h-screen flex-col gap-8 py-6 lg:flex-row">
      <aside className="w-64 h-full lg:sticky lg:top-20 lg:w-72 lg:self-start">
        <SectionMenu tree={tree} activePath={article.path} />
      </aside>

      <main className="w-full flex-1 min-w-0">
        <Breadcrumbs items={article.breadcrumbs} />
        <div className="mt-4 h-px w-full bg-default-300/70" />
        <article className="mt-6">
          <h1 className="pb-6 text-3xl font-bold text-default-900">
            {article.title}
          </h1>
          <div
            className="html-content-block max-w-none text-default-700"
            dangerouslySetInnerHTML={{ __html: parsedContent.html }}
          />
        </article>
      </main>

      {parsedContent.headings.length > 0 && (
        <aside className="w-64 lg:sticky lg:top-20 lg:w-72 lg:self-start">
          <SectionContents headings={parsedContent.headings} />
        </aside>
      )}
    </div>
  );
}

function SectionMenu(props: {
  tree: DocumentArticleTreeNode[];
  activePath: string;
}) {
  return (
    <div className="overflow-hidden rounded-xl border border-default-300 bg-default-50 h-full">
      {props.tree.map((item) => (
        <div
          key={item.id}
          className="border-b border-default-200 p-4 last:border-b-0"
        >
          <MenuItem
            title={item.title}
            path={item.path}
            isActive={props.activePath === item.path}
            isParent
          />

          {item.children.length > 0 && (
            <div className="mt-3 flex flex-col gap-2 pl-4">
              {item.children.map((child) => (
                <MenuItem
                  key={child.id}
                  title={child.title}
                  path={child.path}
                  isActive={props.activePath === child.path}
                />
              ))}
            </div>
          )}
        </div>
      ))}
    </div>
  );
}

function MenuItem(props: {
  title: string;
  path: string;
  isActive: boolean;
  isParent?: boolean;
}) {
  return (
    <Link
      href={`/docs/${props.path}`}
      className={`block transition-colors ${
        props.isParent ? "text-sm font-semibold" : "text-sm"
      } ${
        props.isActive
          ? "text-primary-600"
          : "text-default-700 hover:text-primary-600"
      }`}
    >
      {props.title}
    </Link>
  );
}

function Breadcrumbs(props: { items: DocumentArticleBreadcrumb[] }) {
  return (
    <div className="flex flex-wrap items-center gap-2 text-lg">
      {props.items.map((item, index) => {
        const isLast = index === props.items.length - 1;

        return (
          <div key={item.path} className="flex items-center gap-2">
            {isLast ? (
              <span className="text-default-500">{item.title}</span>
            ) : (
              <Link
                href={`/docs/${item.path}`}
                className="text-primary hover:underline"
              >
                {item.title}
              </Link>
            )}

            {!isLast && <span className="text-default-400">/</span>}
          </div>
        );
      })}
    </div>
  );
}

function SectionContents(props: { headings: Heading[] }) {
  if (props.headings.length === 0) {
    return null;
  }

  return (
    <div className="rounded-xl border border-default-300 bg-default-50 p-4">
      <h2 className="text-2xl font-bold text-default-800">Contents</h2>
      <div className="mt-4 flex flex-col gap-2 border-l-2 border-secondary/20 pl-4">
        {props.headings.map((heading) => (
          <button
            key={heading.id}
            type="button"
            onClick={() => scrollToHeading(heading.id)}
            className={`text-left text-sm text-default-700 transition-colors hover:text-primary-600 ${
              heading.level === 2 ? "pl-4" : ""
            }`}
          >
            {heading.title}
          </button>
        ))}
      </div>
    </div>
  );
}

function parseContent(content: string): ParsedArticleContent {
  if (!content || typeof window === "undefined") {
    return {
      html: content,
      headings: [],
    };
  }

  const parser = new DOMParser();
  const parsedDocument = parser.parseFromString(content, "text/html");
  highlightCodeBlocks(parsedDocument);
  normalizeDocumentationTables(parsedDocument);
  const headingElements = Array.from(parsedDocument.querySelectorAll("h1, h2"));
  const usedIds = new Set<string>();
  const headings: Heading[] = [];

  headingElements.forEach((headingElement, index) => {
    const headingText = headingElement.textContent?.trim() ?? "";
    if (!headingText) {
      return;
    }

    const baseId = slugify(headingText) || `section-${index + 1}`;
    let nextId = baseId;
    let idSuffix = 2;

    while (usedIds.has(nextId)) {
      nextId = `${baseId}-${idSuffix}`;
      idSuffix += 1;
    }

    usedIds.add(nextId);
    headingElement.setAttribute("id", nextId);

    headings.push({
      id: nextId,
      title: headingText,
      level: headingElement.tagName === "H1" ? 1 : 2,
    });
  });

  return {
    html: parsedDocument.body.innerHTML,
    headings,
  };
}

function normalizeDocumentationTables(parsedDocument: Document): void {
  const tables = Array.from(parsedDocument.querySelectorAll("table"));

  tables.forEach((table) => {
    table.classList.add("docs-table");
  });
}

function highlightCodeBlocks(parsedDocument: Document): void {
  const explicitBlocks = Array.from(
    parsedDocument.querySelectorAll("pre.docs-code-block"),
  );
  const fallbackBlocks = Array.from(
    parsedDocument.querySelectorAll("pre"),
  ).filter((block) => !block.classList.contains("docs-code-block"));
  const codeBlocks = [...explicitBlocks, ...fallbackBlocks];

  codeBlocks.forEach((block) => {
    const language = detectCodeLanguage(block);
    block.setAttribute("data-language", language);

    const codeElement = block.querySelector("code");
    const source = codeElement?.textContent ?? block.textContent ?? "";

    if (!source.trim()) {
      return;
    }

    const highlighted = highlightSource(source, language);

    if (codeElement) {
      codeElement.innerHTML = highlighted;
    } else {
      block.innerHTML = `<code>${highlighted}</code>`;
    }
  });
}

function detectCodeLanguage(block: Element): string {
  const fromData = (block.getAttribute("data-language") ?? "")
    .trim()
    .toLowerCase();
  if (fromData === "sparql" || fromData === "sql" || fromData === "json") {
    return fromData;
  }

  const className = block.getAttribute("class") ?? "";
  const classMatch = className.match(/\blanguage-(sparql|sql|json)\b/i);
  const fromClass = classMatch?.[1]?.toLowerCase() ?? "";

  if (fromClass === "sparql" || fromClass === "sql" || fromClass === "json") {
    return fromClass;
  }

  return "sparql";
}

function highlightSource(source: string, language: string): string {
  const keywordSets: Record<string, Set<string>> = {
    sparql: new Set([
      "PREFIX",
      "BASE",
      "SELECT",
      "WHERE",
      "ASK",
      "CONSTRUCT",
      "DESCRIBE",
      "FILTER",
      "OPTIONAL",
      "UNION",
      "GRAPH",
      "BIND",
      "VALUES",
      "ORDER",
      "BY",
      "LIMIT",
      "OFFSET",
      "DISTINCT",
      "REDUCED",
      "FROM",
      "NAMED",
      "SERVICE",
      "MINUS",
      "EXISTS",
      "NOT",
      "IN",
      "AS",
      "A",
    ]),
    sql: new Set([
      "SELECT",
      "FROM",
      "WHERE",
      "JOIN",
      "LEFT",
      "RIGHT",
      "INNER",
      "OUTER",
      "ON",
      "GROUP",
      "BY",
      "ORDER",
      "LIMIT",
      "OFFSET",
      "INSERT",
      "INTO",
      "VALUES",
      "UPDATE",
      "SET",
      "DELETE",
      "CREATE",
      "TABLE",
      "ALTER",
      "DROP",
      "INDEX",
      "AND",
      "OR",
      "NOT",
      "NULL",
      "AS",
      "DISTINCT",
      "UNION",
      "ALL",
      "HAVING",
      "CASE",
      "WHEN",
      "THEN",
      "ELSE",
      "END",
    ]),
    json: new Set(["true", "false", "null"]),
  };

  const tokenRegex =
    /(\/\*[\s\S]*?\*\/|--.*$|#.*$|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|\b\d+(?:\.\d+)?\b|[A-Za-z_][A-Za-z0-9_]*|[^\s])/gm;
  const keywordSet = keywordSets[language] ?? keywordSets.sparql;

  let html = "";
  let cursor = 0;
  let match = tokenRegex.exec(source);

  while (match) {
    const token = match[0];
    const start = match.index;

    if (start > cursor) {
      html += escapeHtml(source.slice(cursor, start));
    }

    html += wrapToken(token, language, keywordSet);
    cursor = start + token.length;
    match = tokenRegex.exec(source);
  }

  if (cursor < source.length) {
    html += escapeHtml(source.slice(cursor));
  }

  return html;
}

function wrapToken(
  token: string,
  language: string,
  keywordSet: Set<string>,
): string {
  const escaped = escapeHtml(token);

  if (
    token.startsWith("/*") ||
    token.startsWith("--") ||
    token.startsWith("#")
  ) {
    return `<span class="docs-code__comment">${escaped}</span>`;
  }

  if (
    (token.startsWith('"') && token.endsWith('"')) ||
    (token.startsWith("'") && token.endsWith("'"))
  ) {
    return `<span class="docs-code__string">${escaped}</span>`;
  }

  if (/^\d+(\.\d+)?$/.test(token)) {
    return `<span class="docs-code__number">${escaped}</span>`;
  }

  if (/^[A-Za-z_][A-Za-z0-9_]*$/.test(token)) {
    if (language === "json" && keywordSet.has(token)) {
      return `<span class="docs-code__keyword">${escaped}</span>`;
    }

    if (language !== "json" && keywordSet.has(token.toUpperCase())) {
      return `<span class="docs-code__keyword">${escaped}</span>`;
    }
  }

  if (/^[()[\]{}.,;:=<>+\-*/%!?|&^~]$/.test(token)) {
    return `<span class="docs-code__operator">${escaped}</span>`;
  }

  return escaped;
}

function escapeHtml(value: string): string {
  return value
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function slugify(value: string): string {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, "")
    .replace(/\s+/g, "-");
}

function scrollToHeading(id: string) {
  const target = document.getElementById(id);
  if (!target) {
    return;
  }

  const top = target.getBoundingClientRect().top + window.scrollY - 110;
  window.scrollTo({ top, behavior: "smooth" });
}
