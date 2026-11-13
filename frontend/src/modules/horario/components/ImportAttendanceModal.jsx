import React, { useState } from 'react';
import { FileSpreadsheet, Upload, X } from 'lucide-react';
import { horarioAPI } from '../services/horarioAPI';
import { parseAttendanceWorkbook } from '../utils/parseAttendance';
import { formatDate } from '../utils/horarioStats';

const ImportAttendanceModal = ({ onClose, onImported }) => {
  const [file, setFile] = useState(null);
  const [preview, setPreview] = useState(null);
  const [result, setResult] = useState(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  const chooseFile = async (selected) => {
    setFile(selected);
    setPreview(null);
    setResult(null);
    setError('');
    if (!selected) return;
    setBusy(true);
    try {
      const parsed = await parseAttendanceWorkbook(selected);
      if (!parsed.registros.length) throw new Error('No se encontraron marcaciones válidas en el archivo.');
      const dates = parsed.registros.map((row) => row.fecha_hora).sort();
      setPreview({
        ...parsed,
        desde: dates[0].slice(0, 10),
        hasta: dates[dates.length - 1].slice(0, 10),
        personas: new Set(parsed.registros.map((row) => row.person_id)).size,
      });
    } catch (readError) {
      setError(readError.message || 'No fue posible leer el archivo.');
    } finally {
      setBusy(false);
    }
  };

  const upload = async () => {
    setBusy(true);
    setError('');
    try {
      const response = await horarioAPI.importAttendance(preview.registros);
      setResult(response.data?.data);
      onImported();
    } catch (requestError) {
      setError(requestError.response?.data?.message || 'No fue posible cargar la asistencia.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true" aria-labelledby="import-title">
      <div className="w-full max-w-lg space-y-4 rounded-3xl bg-white p-6 shadow-xl">
        <div className="flex items-start justify-between">
          <div>
            <h2 id="import-title" className="text-xl font-bold text-slate-900">Cargar asistencia</h2>
            <p className="text-sm text-slate-500">Sube un Excel o archivo de texto (CSV, TSV o TXT) con las columnas Person ID, Name, Time y Attendance Check Point. Solo se contarán filas con el punto de control Asistencia_Puerta1_Lector de tarjetas de entrada1; las marcaciones ya registradas se omiten.</p>
          </div>
          <button type="button" onClick={onClose} aria-label="Cerrar" className="rounded-lg p-1 text-slate-500 hover:bg-slate-100"><X size={18} /></button>
        </div>

        <label className="flex cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center text-sm text-slate-600 hover:border-sky-400">
          <FileSpreadsheet size={28} className="text-sky-600" aria-hidden="true" />
          {file ? file.name : 'Selecciona un archivo .xlsx, .csv, .tsv o .txt'}
          <input type="file" accept=".xlsx,.csv,.tsv,.txt,text/*" className="sr-only" onChange={(event) => chooseFile(event.target.files?.[0] ?? null)} />
        </label>

        {busy && !preview && !result && <p className="text-sm text-slate-500">Leyendo archivo...</p>}
        {error && <div role="alert" className="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700">{error}</div>}

        {preview && !result && (
          <div className="rounded-xl bg-sky-50 p-4 text-sm text-sky-900">
            <p><strong>{preview.registros.length}</strong> marcaciones de <strong>{preview.personas}</strong> personas.</p>
            <p>Periodo: {formatDate(preview.desde)} al {formatDate(preview.hasta)}.</p>
            {preview.ignoradas > 0 && <p>{preview.ignoradas} filas sin datos válidos serán ignoradas.</p>}
            {preview.filtradas > 0 && <p>{preview.filtradas} filas de otros puntos de control no se contarán.</p>}
          </div>
        )}

        {result && (
          <div role="status" className="rounded-xl bg-green-50 p-4 text-sm text-green-800">
            <p><strong>{result.nuevos}</strong> marcaciones nuevas cargadas.</p>
            <p>{result.duplicados} ya existían y se omitieron{result.invalidos ? `; ${result.invalidos} inválidas` : ''}.</p>
          </div>
        )}

        <div className="flex justify-end gap-2">
          <button type="button" onClick={onClose} className="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">{result ? 'Cerrar' : 'Cancelar'}</button>
          {!result && (
            <button type="button" disabled={!preview || busy} onClick={upload} className="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:opacity-50">
              <Upload size={16} /> {busy && preview ? 'Cargando...' : 'Cargar'}
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default ImportAttendanceModal;
