import * as XLSX from 'xlsx'

function sanitizeSheetName(rawName, fallback) {
  const cleaned = (rawName || fallback)
    .replace(/[\\/?*[\]:]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
  return cleaned.slice(0, 31) || fallback
}

export function exportAssessmentExcel(exportPayload) {
  const { class: classInfo, rubrics = [], examiners = [], rows = [] } = exportPayload
  const wb = XLSX.utils.book_new()
  const usedSheetNames = new Set()

  function getUniqueSheetName(desiredName, fallback) {
    let base = sanitizeSheetName(desiredName, fallback)
    let candidate = base
    let counter = 2
    while (usedSheetNames.has(candidate.toLowerCase())) {
      const suffix = `_${counter++}`
      candidate = `${base.slice(0, 31 - suffix.length)}${suffix}`
    }
    usedSheetNames.add(candidate.toLowerCase())
    return candidate
  }

  // 1. Buat Sheet untuk setiap Penguji (Data nilai per masing-masing penguji)
  examiners.forEach((examiner, exIdx) => {
    const examinerSheetRows = rows.map((row, index) => {
      const exAssessment = row.examiner_scores?.[examiner.id] || null

      const item = {
        No: index + 1,
        NIS: row.nis,
        'Nama Siswa': row.name,
        Jurusan: row.major || classInfo?.major || 'PPLG',
        Kelas: row.class_name,
        'Tahun Ajaran': row.academic_year,
        Prioritas: row.is_priority ? `Ya (${row.priority_note || '-'})` : 'Tidak',
        'Nama Penguji': examiner.name,
        'Status (Penguji Ini)': exAssessment
          ? exAssessment.status === 'kompeten'
            ? 'KOMPETEN'
            : 'REMEDIAL'
          : 'BELUM DINILAI',
        'Nilai Penguji':
          exAssessment?.final_score !== null && exAssessment?.final_score !== undefined
            ? Number(exAssessment.final_score)
            : '-',
      }

      rubrics.forEach((rubric) => {
        const detail = exAssessment?.scores?.[rubric.id]
        item[`B${rubric.item_order}: ${rubric.title} (${rubric.weight}%)`] =
          detail?.raw_score !== null && detail?.raw_score !== undefined
            ? Number(detail.raw_score)
            : '-'
      })

      item['Quick Tags'] =
        Array.isArray(exAssessment?.quick_tags) && exAssessment.quick_tags.length > 0
          ? exAssessment.quick_tags.join(', ')
          : '-'
      item['Catatan Penguji'] = exAssessment?.examiner_note || '-'
      item['Durasi (Detik)'] = exAssessment?.duration_seconds || 0
      item['Jumlah Revisi'] = exAssessment?.revision_count || 0
      item['Waktu Submit'] = exAssessment?.submitted_at || '-'

      return item
    })

    const wsExaminer = XLSX.utils.json_to_sheet(examinerSheetRows)
    wsExaminer['!cols'] = [
      { wch: 5 },
      { wch: 14 },
      { wch: 28 },
      { wch: 10 },
      { wch: 14 },
      { wch: 14 },
      { wch: 22 },
      { wch: 24 },
      { wch: 18 },
      { wch: 14 },
      ...rubrics.map(() => ({ wch: 18 })),
      { wch: 28 },
      { wch: 32 },
      { wch: 14 },
      { wch: 14 },
      { wch: 20 },
    ]

    const sheetTitle = getUniqueSheetName(
      `P${exIdx + 1}_${examiner.name}`,
      `Penguji_${exIdx + 1}`
    )
    XLSX.utils.book_append_sheet(wb, wsExaminer, sheetTitle)
  })

  // 2. Sheet Referensi Rubrik Penilaian
  const rubricRows = rubrics.map((r) => ({
    Jurusan: r.major || classInfo?.major || 'PPLG',
    'No Butir': r.item_order,
    Halaman: r.page_number,
    'Judul Halaman': r.page_title || `Halaman ${r.page_number}`,
    'Aspek / Judul Rubrik': r.title,
    'Bobot (%)': Number(r.weight),
    Indikator: Array.isArray(r.indicators) ? r.indicators.join('; ') : '',
  }))
  const wsRubric = XLSX.utils.json_to_sheet(rubricRows)
  wsRubric['!cols'] = [
    { wch: 10 },
    { wch: 10 },
    { wch: 10 },
    { wch: 32 },
    { wch: 42 },
    { wch: 12 },
    { wch: 60 },
  ]
  XLSX.utils.book_append_sheet(wb, wsRubric, getUniqueSheetName('Rubrik_Penilaian', 'Rubrik'))

  // 3. Sheet Terakhir (Akhir Sheet): "total_hasil"
  // Nilai total_hasil = Jumlah Nilai seluruh penguji dibagi banyaknya penguji yang telah menilai
  const totalHasilRows = rows.map((row, index) => {
    const base = {
      No: index + 1,
      NIS: row.nis,
      'Nama Siswa': row.name,
      Jurusan: row.major || classInfo?.major || 'PPLG',
      Kelas: row.class_name,
      'Tahun Ajaran': row.academic_year,
      Prioritas: row.is_priority ? `Ya (${row.priority_note || '-'})` : 'Tidak',
    }

    examiners.forEach((examiner, exIdx) => {
      const exScore = row.examiner_scores?.[examiner.id]?.final_score
      base[`Nilai P${exIdx + 1} (${examiner.name})`] =
        exScore !== null && exScore !== undefined ? Number(exScore) : '-'
    })

    base['Total Nilai (Jumlah)'] =
      row.sum_score !== null && row.sum_score !== undefined ? Number(row.sum_score) : '-'
    base['Banyak Penguji Menilai'] = Number(row.graded_examiner_count || 0)
    base['total_hasil'] =
      row.total_hasil !== null && row.total_hasil !== undefined ? Number(row.total_hasil) : '-'
    base['Status Akhir'] =
      row.status === 'kompeten'
        ? 'KOMPETEN'
        : row.status === 'remedial'
          ? 'REMEDIAL'
          : 'BELUM DINILAI'

    rubrics.forEach((rubric) => {
      const avgDetail = row.scores?.[rubric.id]
      base[`Rata2 B${rubric.item_order}: ${rubric.title} (${rubric.weight}%)`] =
        avgDetail?.raw_score !== null && avgDetail?.raw_score !== undefined
          ? Number(avgDetail.raw_score)
          : '-'
    })

    base['Penguji yang Menilai'] = row.examiner_name || '-'
    base['Catatan Gabungan Penguji'] = row.examiner_note || '-'

    return base
  })

  const wsTotalHasil = XLSX.utils.json_to_sheet(totalHasilRows)
  wsTotalHasil['!cols'] = [
    { wch: 5 },
    { wch: 14 },
    { wch: 28 },
    { wch: 10 },
    { wch: 14 },
    { wch: 14 },
    { wch: 22 },
    ...examiners.map(() => ({ wch: 22 })),
    { wch: 18 },
    { wch: 22 },
    { wch: 16 },
    { wch: 16 },
    ...rubrics.map(() => ({ wch: 20 })),
    { wch: 28 },
    { wch: 38 },
  ]

  XLSX.utils.book_append_sheet(wb, wsTotalHasil, 'total_hasil')

  const safeClassName = (classInfo?.name || 'Semua_Kelas').replace(/[^a-zA-Z0-9_-]+/g, '_')
  const dateStamp = new Date().toISOString().slice(0, 10)
  XLSX.writeFile(wb, `Rekap_Ujikom_${safeClassName}_${dateStamp}.xlsx`)
}
