import * as XLSX from 'xlsx'

export function exportAssessmentExcel(exportPayload) {
  const { class: classInfo, rubrics, rows } = exportPayload

  const sheetRows = rows.map((row, index) => {
    const base = {
      No: index + 1,
      NIS: row.nis,
      'Nama Siswa': row.name,
      Kelas: row.class_name,
      'Tahun Ajaran': row.academic_year,
      Prioritas: row.is_priority ? `Ya (${row.priority_note || '-'})` : 'Tidak',
      Status:
        row.status === 'kompeten'
          ? 'KOMPETEN'
          : row.status === 'remedial'
            ? 'REMEDIAL'
            : 'BELUM DINILAI',
      'Nilai Akhir': row.final_score !== null ? Number(row.final_score) : '-',
    }

    rubrics.forEach((rubric) => {
      const detail = row.scores?.[rubric.id]
      base[`B${rubric.item_order}: ${rubric.title} (${rubric.weight}%)`] =
        detail?.raw_score !== null && detail?.raw_score !== undefined
          ? Number(detail.raw_score)
          : '-'
    })

    base['Quick Tags'] = Array.isArray(row.quick_tags) && row.quick_tags.length > 0
      ? row.quick_tags.join(', ')
      : '-'
    base['Catatan Penguji'] = row.examiner_note || '-'
    base['Nama Penguji'] = row.examiner_name || '-'
    base['Durasi (Detik)'] = row.duration_seconds || 0
    base['Jumlah Revisi'] = row.revision_count || 0
    base['Waktu Submit'] = row.submitted_at || '-'

    return base
  })

  const rubricRows = rubrics.map((r) => ({
    'No Butir': r.item_order,
    Halaman: r.page_number,
    'Aspek / Judul Rubrik': r.title,
    'Bobot (%)': Number(r.weight),
    Indikator: Array.isArray(r.indicators) ? r.indicators.join('; ') : '',
  }))

  const wb = XLSX.utils.book_new()
  const wsData = XLSX.utils.json_to_sheet(sheetRows)
  const wsRubric = XLSX.utils.json_to_sheet(rubricRows)

  wsData['!cols'] = [
    { wch: 5 },
    { wch: 14 },
    { wch: 28 },
    { wch: 14 },
    { wch: 14 },
    { wch: 24 },
    { wch: 15 },
    { wch: 12 },
    ...rubrics.map(() => ({ wch: 18 })),
    { wch: 30 },
    { wch: 32 },
    { wch: 22 },
    { wch: 14 },
    { wch: 14 },
    { wch: 20 },
  ]

  wsRubric['!cols'] = [
    { wch: 10 },
    { wch: 10 },
    { wch: 42 },
    { wch: 12 },
    { wch: 60 },
  ]

  XLSX.utils.book_append_sheet(wb, wsData, 'Rekap Nilai Ujikom')
  XLSX.utils.book_append_sheet(wb, wsRubric, 'Rubrik Penilaian')

  const safeClassName = (classInfo?.name || 'Semua_Kelas').replace(/[^a-zA-Z0-9_-]+/g, '_')
  const dateStamp = new Date().toISOString().slice(0, 10)
  XLSX.writeFile(wb, `Rekap_Ujikom_PPLG_${safeClassName}_${dateStamp}.xlsx`)
}
