import React, { useMemo, useState } from 'react';
import { ChevronDown, Search } from 'lucide-react';
import faqCategories from '../data/faqData';

const INLINE_PATTERN = /(\*\*[^*]+\*\*|\[[^\]]+\]\([^)]+\))/g;
const SAFE_LINK = /^(https?:|mailto:)/i;

const renderInline = (text) => text.split(INLINE_PATTERN).map((part, index) => {
  if (part.startsWith('**') && part.endsWith('**')) {
    return <strong key={index} className="font-semibold text-slate-900">{part.slice(2, -2)}</strong>;
  }
  const link = part.match(/^\[([^\]]+)\]\(([^)]+)\)$/);
  if (link && SAFE_LINK.test(link[2])) {
    return (
      <a key={index} href={link[2]} target="_blank" rel="noopener noreferrer" className="font-semibold text-orange-600 hover:underline break-words">
        {link[1]}
      </a>
    );
  }
  return <React.Fragment key={index}>{part}</React.Fragment>;
});

const Block = ({ block }) => {
  if (block.type === 'p') {
    return <p>{renderInline(block.text)}</p>;
  }
  const ListTag = block.type === 'steps' ? 'ol' : 'ul';
  const listStyle = block.type === 'steps' ? 'list-decimal' : 'list-disc';
  return (
    <ListTag className={`${listStyle} space-y-2 pl-6`}>
      {block.items.map((item, index) => <li key={index}>{renderInline(item)}</li>)}
    </ListTag>
  );
};

const blockText = (block) => (block.type === 'p' ? block.text : block.items.join(' '));

export const FaqSection = () => {
  const [query, setQuery] = useState('');

  const categories = useMemo(() => {
    const term = query.trim().toLowerCase();
    if (!term) return faqCategories;
    return faqCategories
      .map((category) => ({
        ...category,
        questions: category.questions.filter((item) => (
          `${item.q} ${item.blocks.map(blockText).join(' ')}`.toLowerCase().includes(term)
        )),
      }))
      .filter((category) => category.questions.length > 0);
  }, [query]);

  return (
    <section className="mx-auto max-w-3xl space-y-6">
      <div className="text-center">
        <h2 className="text-2xl font-bold text-slate-900">Preguntas frecuentes</h2>
        <p className="mt-1 text-sm text-slate-500">Encuentra respuestas rápidas sobre nómina, caja, atención al usuario y sistemas.</p>
      </div>

      <div className="relative">
        <Search size={16} className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
        <input
          type="search"
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder="Buscar una pregunta..."
          aria-label="Buscar en preguntas frecuentes"
          className="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm"
        />
      </div>

      {categories.length === 0 && (
        <div className="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
          No se encontraron preguntas para tu búsqueda.
        </div>
      )}

      {categories.map((category) => (
        <div key={category.id}>
          <h3 className="mb-3 ml-1 border-l-4 border-orange-600 pl-3 text-lg font-bold text-slate-900">{category.title}</h3>
          <div className="space-y-3">
            {category.questions.map((item) => (
              <details key={item.q} className="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                <summary className="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 text-sm font-bold text-slate-900 transition hover:bg-orange-50 group-open:border-b group-open:border-slate-200 group-open:bg-orange-50 [&::-webkit-details-marker]:hidden">
                  <span>{item.q}</span>
                  <ChevronDown size={16} className="shrink-0 transition-transform group-open:rotate-180" />
                </summary>
                <div className="space-y-3 px-6 py-4 text-sm leading-relaxed text-slate-600">
                  {item.blocks.map((block, index) => <Block key={index} block={block} />)}
                </div>
              </details>
            ))}
          </div>
        </div>
      ))}
    </section>
  );
};

export default FaqSection;
