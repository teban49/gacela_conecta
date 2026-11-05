import React from 'react';
import { Newspaper, Trash2 } from 'lucide-react';

const formatDate = (value) => {
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? ''
    : date.toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' });
};

const initialsOf = (noticia) => {
  const name = `${noticia.AutorNombre ?? ''} ${noticia.AutorApellidos ?? ''}`.trim();
  return name ? name.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase() : 'GG';
};

// Las noticias se muestran como publicaciones completas, sin abrir otra página
export const NewsFeed = ({ noticias, onDelete }) => {
  if (!noticias.length) {
    return (
      <div className="mx-auto max-w-2xl rounded-3xl border border-slate-200 bg-white p-12 text-center text-slate-500">
        No hay noticias o comunicados publicados actualmente.
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      {noticias.map((noticia) => {
        const author = `${noticia.AutorNombre ?? ''} ${noticia.AutorApellidos ?? ''}`.trim() || 'Grupo Gacela';
        return (
          <article key={noticia.Id} className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <header className="flex items-center gap-3 px-5 pt-5">
              <div className="flex h-11 w-11 items-center justify-center rounded-full bg-orange-600 text-sm font-bold text-white">
                {initialsOf(noticia)}
              </div>
              <div>
                <p className="text-sm font-semibold text-slate-900">{author}</p>
                <p className="text-xs text-slate-500">{formatDate(noticia.FechaPublicacion)}</p>
              </div>
              <Newspaper size={16} className="ml-auto text-slate-300" aria-hidden="true" />
              {onDelete && (
                <button
                  type="button"
                  onClick={() => onDelete(noticia)}
                  aria-label={`Eliminar noticia ${noticia.Titulo}`}
                  className="rounded-lg p-2 text-red-600 hover:bg-red-50"
                >
                  <Trash2 size={16} />
                </button>
              )}
            </header>

            <div className="space-y-2 px-5 py-4">
              <h3 className="text-xl font-bold leading-snug text-slate-900">{noticia.Titulo}</h3>
              {noticia.Resumen && noticia.Resumen !== noticia.Contenido && (
                <p className="text-sm font-medium text-slate-600">{noticia.Resumen}</p>
              )}
              <p className="whitespace-pre-line text-[15px] leading-relaxed text-slate-700">{noticia.Contenido}</p>
            </div>

            {noticia.ImagenUrl && (
              <img src={noticia.ImagenUrl} alt={noticia.Titulo} className="max-h-[36rem] w-full bg-slate-100 object-contain" />
            )}
          </article>
        );
      })}
    </div>
  );
};

export default NewsFeed;
