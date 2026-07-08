"use client";

import {
  createContext,
  ReactNode,
  useContext,
  useEffect,
  useRef,
  useState,
} from "react";
import { FiMoreVertical } from "react-icons/fi";

const STORAGE_KEY = "floating-dock-right";
const LEGACY_STORAGE_KEY = "floating-dock-x";
const EDGE_MARGIN = 16;
const DRAG_THRESHOLD = 4;
// Widest panel (FeedbackWidget, 360px) plus a comfortable margin. Below this
// much room on the left, panels flip to open rightwards instead of overflowing.
const PANEL_FLIP_THRESHOLD = 380;

type DockAlign = "left" | "right";

const DockAlignContext = createContext<DockAlign>("right");

export function useDockAlign(): DockAlign {
  return useContext(DockAlignContext);
}

export default function FloatingDock({ children }: { children: ReactNode }) {
  const dockRef = useRef<HTMLDivElement>(null);
  const dragState = useRef<{
    pointerId: number;
    startClientX: number;
    startRight: number;
    dragging: boolean;
  } | null>(null);
  const rightOffsetRef = useRef(EDGE_MARGIN);

  const [rightOffset, setRightOffset] = useState(EDGE_MARGIN);
  const [viewportWidth, setViewportWidth] = useState(0);

  function clampRightOffset(value: number): number {
    const width = dockRef.current?.offsetWidth ?? 0;
    const max = Math.max(EDGE_MARGIN, window.innerWidth - width - EDGE_MARGIN);

    return Math.min(Math.max(value, EDGE_MARGIN), max);
  }

  function updateRightOffset(value: number) {
    const nextRightOffset = clampRightOffset(value);

    rightOffsetRef.current = nextRightOffset;
    setRightOffset(nextRightOffset);
  }

  useEffect(() => {
    const storedRight = window.localStorage.getItem(STORAGE_KEY);
    const storedLeft = window.localStorage.getItem(LEGACY_STORAGE_KEY);

    if (storedRight !== null) {
      updateRightOffset(Number(storedRight));
    } else if (storedLeft !== null) {
      const width = dockRef.current?.offsetWidth ?? 0;

      updateRightOffset(window.innerWidth - width - Number(storedLeft));
    }

    setViewportWidth(window.innerWidth);

    function handleResize() {
      setViewportWidth(window.innerWidth);
      updateRightOffset(rightOffsetRef.current);
    }

    window.addEventListener("resize", handleResize);

    return () => window.removeEventListener("resize", handleResize);
  }, []);

  function handlePointerDown(event: React.PointerEvent<HTMLButtonElement>) {
    event.currentTarget.setPointerCapture(event.pointerId);
    dragState.current = {
      pointerId: event.pointerId,
      startClientX: event.clientX,
      startRight: rightOffsetRef.current,
      dragging: false,
    };
  }

  function handlePointerMove(event: React.PointerEvent<HTMLButtonElement>) {
    const drag = dragState.current;

    if (!drag || drag.pointerId !== event.pointerId) {
      return;
    }

    const delta = event.clientX - drag.startClientX;

    if (!drag.dragging && Math.abs(delta) < DRAG_THRESHOLD) {
      return;
    }

    drag.dragging = true;
    updateRightOffset(drag.startRight - delta);
  }

  function handlePointerUp(event: React.PointerEvent<HTMLButtonElement>) {
    const drag = dragState.current;

    if (drag?.dragging) {
      window.localStorage.setItem(STORAGE_KEY, String(rightOffsetRef.current));
    }

    dragState.current = null;
  }

  const dockWidth = dockRef.current?.offsetWidth ?? 0;
  const x = viewportWidth - dockWidth - rightOffset;
  const align: DockAlign = x < PANEL_FLIP_THRESHOLD ? "left" : "right";

  return (
    <div
      ref={dockRef}
      className="pointer-events-none flex items-end gap-2"
      style={{
        bottom: "max(16px, env(safe-area-inset-bottom))",
        position: "fixed",
        right: rightOffset,
        zIndex: 1000,
      }}
    >
      <DockAlignContext.Provider value={align}>
        {children}
      </DockAlignContext.Provider>

      <button
        aria-label="Drag to move"
        className="pointer-events-auto flex h-9 w-9 min-w-9 cursor-grab touch-none items-center justify-center rounded-full border border-default-200 bg-white text-foreground-500 shadow-md active:cursor-grabbing dark:border-default-100 dark:bg-zinc-950"
        type="button"
        onPointerDown={handlePointerDown}
        onPointerMove={handlePointerMove}
        onPointerUp={handlePointerUp}
        onPointerCancel={handlePointerUp}
      >
        <FiMoreVertical className="h-4 w-4 rotate-90" />
      </button>
    </div>
  );
}
