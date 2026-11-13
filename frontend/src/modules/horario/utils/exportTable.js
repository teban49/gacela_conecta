import { downloadRowsAsXlsx } from '../../../utils/xlsx';

export { createXlsx, downloadRowsAsXlsx } from '../../../utils/xlsx';

export const downloadTableAsXlsx = (table, fileName) => {
  const rows = [...table.rows].map((row) => [...row.cells].map((cell) => cell.innerText));
  downloadRowsAsXlsx(rows, fileName);
};
