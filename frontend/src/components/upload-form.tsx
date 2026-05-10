"use client";

import { useRef, useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";

export function UploadForm() {
  const inputRef = useRef<HTMLInputElement>(null);
  const router = useRouter();
  const [pending, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);
  const [preview, setPreview] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  function onPick(file: File) {
    setError(null);
    setPreview(URL.createObjectURL(file));
    setBusy(true);
    api
      .uploadReceipt(file)
      .then((expense) => {
        startTransition(() => {
          router.refresh();
          router.push(`/expenses/${expense.id}`);
        });
      })
      .catch((e: Error) => setError(e.message))
      .finally(() => setBusy(false));
  }

  const loading = busy || pending;

  return (
    <div className="rounded-xl border border-dashed border-foreground/25 p-8 text-center">
      <input
        ref={inputRef}
        type="file"
        accept="image/*"
        className="hidden"
        onChange={(e) => {
          const f = e.target.files?.[0];
          if (f) onPick(f);
        }}
      />

      {preview && (
        <img
          src={preview}
          alt="Receipt preview"
          className="mx-auto mb-4 max-h-48 rounded-md border border-foreground/10"
        />
      )}

      <div className="space-y-3">
        <div className="text-sm text-foreground/70">
          Upload a receipt image. We&apos;ll extract the data with AI and check it
          against your policies.
        </div>
        <button
          type="button"
          onClick={() => inputRef.current?.click()}
          disabled={loading}
          className="inline-flex items-center gap-2 rounded-md bg-foreground px-4 py-2 text-sm font-medium text-background hover:opacity-90 disabled:opacity-50"
        >
          {loading ? "Extracting…" : "Choose receipt image"}
        </button>
      </div>

      {error && (
        <div className="mt-4 rounded-md bg-rose-50 p-3 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
          {error}
        </div>
      )}
    </div>
  );
}
