let cachedLogoDataUrl = null

async function getLogoDataUrl() {
  if (cachedLogoDataUrl) return cachedLogoDataUrl
  try {
    const img = new Image()
    img.crossOrigin = 'anonymous'
    const loaded = await new Promise((resolve, reject) => {
      img.onload = () => resolve(true)
      img.onerror = reject
      img.src = '/marhasupdate.webp'
    })
    if (loaded) {
      const canvas = document.createElement('canvas')
      canvas.width = img.naturalWidth || 96
      canvas.height = img.naturalHeight || 96
      const ctx = canvas.getContext('2d')
      ctx.drawImage(img, 0, 0)
      cachedLogoDataUrl = canvas.toDataURL('image/png')
      return cachedLogoDataUrl
    }
  } catch {
    // Fallback ke URL absolut jika gagal konversi canvas
  }
  return `${window.location.origin}/marhasupdate.webp`
}

function escapeHtml(val) {
  if (val === null || val === undefined) return ''
  return String(val)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

function fmtScore(val) {
  if (val === null || val === undefined || val === '-' || Number.isNaN(Number(val))) {
    return '-'
  }
  return Number(val).toFixed(2)
}

function getDocumentStyles(orientation = 'portrait') {
  const isLandscape = orientation === 'landscape'
  const pageSize = isLandscape ? '29.7cm 21cm' : '21cm 29.7cm'

  return `
    @page {
      size: A4 ${orientation};
      margin: 0mm;
    }
    @page Section1 {
      size: ${pageSize};
      mso-page-orientation: ${orientation};
      margin: 1.4cm 1.6cm 1.4cm 1.6cm;
      mso-header-margin: 0cm;
      mso-footer-margin: 0cm;
    }
    div.Section1 {
      page: Section1;
    }
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 10pt;
      color: #0f172a;
      line-height: 1.38;
      margin: 0;
      padding: 0;
      background: #ffffff;
    }
    .report-page {
      width: 100%;
      box-sizing: border-box;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .report-page:not(:last-child) {
      page-break-after: always;
      break-after: page;
    }
    .kop-table {
      width: 100%;
      border-collapse: collapse;
      border-bottom: 3px double #0f172a;
      margin-bottom: 12px;
    }
    .kop-table td {
      border: none;
      vertical-align: middle;
      padding: 4px 4px 8px 4px;
    }
    .kop-title {
      font-size: 14.5pt;
      font-weight: bold;
      text-transform: uppercase;
      margin: 0;
      letter-spacing: 0.5px;
      color: #0f172a;
    }
    .kop-subtitle {
      font-size: 10.5pt;
      font-weight: bold;
      margin: 2px 0 0 0;
      color: #1e293b;
    }
    .kop-desc {
      font-size: 9pt;
      margin: 2px 0 0 0;
      color: #475569;
    }
    .doc-heading {
      text-align: center;
      margin: 8px 0 12px 0;
    }
    .doc-heading h2 {
      font-size: 12pt;
      font-weight: bold;
      text-transform: uppercase;
      margin: 0;
      text-decoration: underline;
    }
    .doc-heading p {
      font-size: 9.5pt;
      margin: 2px 0 0 0;
      color: #334155;
    }
    .info-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      font-size: 9.5pt;
    }
    .info-table td {
      padding: 2.5px 6px;
      vertical-align: top;
      border: none;
    }
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      font-size: 9.5pt;
    }
    .data-table th,
    .data-table td {
      border: 1pt solid #334155;
      padding: 5.5px 8px;
      vertical-align: middle;
    }
    .data-table th {
      background-color: #f1f5f9;
      font-weight: bold;
      text-align: center;
      color: #0f172a;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .font-bold { font-weight: bold; }
    .bg-final { background-color: #eef2ff; font-weight: bold; font-size: 10pt; }
    .badge-kompeten {
      color: #065f46;
      font-weight: bold;
    }
    .badge-remedial {
      color: #9f1239;
      font-weight: bold;
    }
    .note-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 14px;
      font-size: 9pt;
    }
    .note-table td {
      border: 1pt solid #64748b;
      background-color: #f8fafc;
      padding: 7px 10px;
    }
    .signature-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 12px;
      page-break-inside: avoid;
    }
    .signature-table td {
      border: none;
      width: 50%;
      text-align: center;
      vertical-align: top;
      padding: 2px 12px;
      font-size: 9.5pt;
    }
    .sig-name {
      font-weight: bold;
      text-decoration: underline;
    }
  `
}

function buildKopHeaderHtml(logoUrl, major) {
  const majorFull =
    major === 'TMS'
      ? 'Teknik Mekanik / Sepeda Motor (TMS)'
      : 'Pengembangan Perangkat Lunak dan Gim (PPLG / RPL)'

  return `
    <table class="kop-table" style="width:100%;border-collapse:collapse;border-bottom:2.25pt double #0f172a;margin-bottom:10px;">
      <tr>
        <td style="width: 72px; text-align: center; border:none; padding:4px 4px 8px 4px;">
          <img src="${logoUrl}" width="60" height="60" alt="Logo Marhas" style="width:60px;height:60px;object-fit:contain;" />
        </td>
        <td style="text-align: center; border:none; padding:4px 4px 8px 4px;">
          <p class="kop-title" style="font-size:14.5pt;font-weight:bold;margin:0;color:#0f172a;">MARHAS UJIKOM</p>
          <p class="kop-subtitle" style="font-size:10.5pt;font-weight:bold;margin:2px 0 0 0;color:#1e293b;">SISTEM PENILAIAN UJI KOMPETENSI KEAHLIAN (UJIKOM)</p>
          <p class="kop-desc" style="font-size:9pt;margin:2px 0 0 0;color:#475569;">Program Keahlian: ${escapeHtml(majorFull)}</p>
        </td>
        <td style="width: 72px; border:none;">&nbsp;</td>
      </tr>
    </table>
  `
}

function buildSignatureBlockHtml({ internalName, externalName, dateLocation }) {
  const intDisplay = internalName?.trim() || '................................................'
  const extDisplay = externalName?.trim() || '................................................'
  const dateDisplay =
    dateLocation?.trim() ||
    `Bandung, ${new Date().toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })}`

  // Menggunakan 3 baris tabel terpisah & baris ruang tanda tangan eksplisit (<br/>)
  // agar di Microsoft Word maupun PDF terdapat ruang tanda tangan ~75px dan nama sejajar lurus
  return `
    <table class="signature-table" style="width:100%;border-collapse:collapse;margin-top:12px;">
      <tr>
        <td style="width:50%;text-align:center;vertical-align:bottom;border:none;padding:2px 12px;">
          <p style="margin:0 0 2px 0;font-size:9.5pt;">&nbsp;</p>
          <p style="margin:0;font-weight:bold;font-size:10pt;">Penguji Internal</p>
          <p style="margin:2px 0 0 0;font-size:8.5pt;color:#475569;">( Guru Produktif / Sekolah )</p>
        </td>
        <td style="width:50%;text-align:center;vertical-align:bottom;border:none;padding:2px 12px;">
          <p style="margin:0 0 2px 0;font-size:9.5pt;">${escapeHtml(dateDisplay)}</p>
          <p style="margin:0;font-weight:bold;font-size:10pt;">Penguji Eksternal</p>
          <p style="margin:2px 0 0 0;font-size:8.5pt;color:#475569;">( Asesor DUDIKA / Industri )</p>
        </td>
      </tr>
      <tr style="height:72px;">
        <td style="height:72px;text-align:center;border:none;padding:0;">
          <p style="margin:0;line-height:16pt;font-size:10pt;">&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;</p>
        </td>
        <td style="height:72px;text-align:center;border:none;padding:0;">
          <p style="margin:0;line-height:16pt;font-size:10pt;">&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;</p>
        </td>
      </tr>
      <tr>
        <td style="width:50%;text-align:center;vertical-align:top;border:none;padding:2px 12px;">
          <p class="sig-name" style="margin:0;font-weight:bold;text-decoration:underline;font-size:10pt;">
            ( ${escapeHtml(intDisplay)} )
          </p>
        </td>
        <td style="width:50%;text-align:center;vertical-align:top;border:none;padding:2px 12px;">
          <p class="sig-name" style="margin:0;font-weight:bold;text-decoration:underline;font-size:10pt;">
            ( ${escapeHtml(extDisplay)} )
          </p>
        </td>
      </tr>
    </table>
  `
}

export function buildSingleStudentSheetHtml({
  row,
  rubrics = [],
  examiners = [],
  logoUrl,
  reportMeta = {},
}) {
  const internalName =
    reportMeta.internalExaminerName ||
    (Array.isArray(row.internal_examiners) && row.internal_examiners.length
      ? row.internal_examiners.join(', ')
      : examiners
          .filter((e) => e.examiner_type !== 'eksternal')
          .map((e) => e.name)
          .join(', '))

  const externalName =
    reportMeta.externalExaminerName ||
    (Array.isArray(row.external_examiners) && row.external_examiners.length
      ? row.external_examiners.join(', ')
      : examiners
          .filter((e) => e.examiner_type === 'eksternal')
          .map((e) => e.name)
          .join(', '))

  const statusText =
    row.status === 'kompeten'
      ? 'KOMPETEN (LULUS)'
      : row.status === 'remedial'
        ? 'PERLU REMEDIAL'
        : 'BELUM DINILAI'
  const statusClass = row.status === 'kompeten' ? 'badge-kompeten' : 'badge-remedial'

  const rubricRowsHtml = rubrics
    .map((rubric, index) => {
      const avgDetail = row.scores?.[rubric.id]
      const avgRaw = avgDetail?.raw_score

      return `
        <tr>
          <td class="text-center" style="border:1pt solid #334155;padding:5.5px 8px;text-align:center;">${index + 1}</td>
          <td style="border:1pt solid #334155;padding:5.5px 8px;">
            <span class="font-bold" style="font-weight:bold;">${escapeHtml(rubric.title)}</span>
          </td>
          <td class="text-center font-bold" style="border:1pt solid #334155;padding:5.5px 8px;text-align:center;font-weight:bold;">
            ${fmtScore(avgRaw)}
          </td>
        </tr>
      `
    })
    .join('')

  const tagsText =
    Array.isArray(row.quick_tags) && row.quick_tags.length > 0 ? row.quick_tags.join(', ') : '-'

  return `
    <div class="report-page">
      ${buildKopHeaderHtml(logoUrl, row.major)}

      <div class="doc-heading">
        <h2>LEMBAR LAPORAN HASIL PENILAIAN PESERTA UJIKOM</h2>
        <p>Tahun Ajaran ${escapeHtml(row.academic_year || '2026/2027')}</p>
      </div>

      <table class="info-table">
        <tr>
          <td style="width: 18%;"><strong>Nama Siswa</strong></td>
          <td style="width: 2%;">:</td>
          <td style="width: 35%;"><strong>${escapeHtml(row.name)}</strong></td>
          <td style="width: 18%;"><strong>Kelas / Jurusan</strong></td>
          <td style="width: 2%;">:</td>
          <td style="width: 25%;">${escapeHtml(row.class_name)} (${escapeHtml(row.major)})</td>
        </tr>
        <tr>
          <td><strong>NIS</strong></td>
          <td>:</td>
          <td>${escapeHtml(row.nis)}</td>
          <td><strong>Status Akhir</strong></td>
          <td>:</td>
          <td><span class="${statusClass}">${escapeHtml(statusText)}</span></td>
        </tr>
        <tr>
          <td><strong>Waktu Penilaian</strong></td>
          <td>:</td>
          <td colspan="4">${escapeHtml(row.submitted_at || '-')}</td>
        </tr>
      </table>

      <table class="data-table" style="width:100%;border-collapse:collapse;margin-bottom:10px;">
        <thead>
          <tr>
            <th style="width: 42px;border:1pt solid #334155;background-color:#f1f5f9;padding:6px 8px;text-align:center;">No</th>
            <th style="border:1pt solid #334155;background-color:#f1f5f9;padding:6px 8px;text-align:center;">Aspek Kompetensi / Butir Rubrik Penilaian</th>
            <th style="width: 155px;border:1pt solid #334155;background-color:#f1f5f9;padding:6px 8px;text-align:center;">Rata-Rata Skor (0-100)</th>
          </tr>
        </thead>
        <tbody>
          ${rubricRowsHtml}
          <tr class="bg-final">
            <td colspan="2" class="text-right" style="border:1pt solid #334155;background-color:#eef2ff;padding:6px 10px;text-align:right;font-weight:bold;">
              NILAI AKHIR RATA-RATA
            </td>
            <td class="text-center" style="border:1pt solid #334155;background-color:#eef2ff;padding:6px 8px;text-align:center;font-weight:bold;font-size:11.5pt;">
              ${fmtScore(row.total_hasil)}
            </td>
          </tr>
        </tbody>
      </table>

      <table class="note-table" style="width:100%;border-collapse:collapse;margin-bottom:12px;">
        <tr>
          <td style="border:1pt solid #64748b;background-color:#f8fafc;padding:7px 10px;font-size:9pt;">
            <p style="margin:0 0 3px 0;"><strong>Tag Evaluasi Penguji:</strong> ${escapeHtml(tagsText)}</p>
            <p style="margin:0;"><strong>Catatan Penguji (Internal &amp; Eksternal):</strong> ${escapeHtml(row.examiner_note || 'Tidak ada catatan tambahan.')}</p>
          </td>
        </tr>
      </table>

      ${buildSignatureBlockHtml({
        internalName,
        externalName,
        dateLocation: reportMeta.dateLocation,
      })}
    </div>
  `
}

export function buildClassSummaryReportHtml({
  payload,
  logoUrl,
  reportMeta = {},
}) {
  const { class: classInfo, rubrics = [], examiners = [], rows = [] } = payload
  const major = classInfo?.major || 'PPLG'

  const internalName =
    reportMeta.internalExaminerName ||
    examiners
      .filter((e) => e.examiner_type !== 'eksternal')
      .map((e) => e.name)
      .join(', ')

  const externalName =
    reportMeta.externalExaminerName ||
    examiners
      .filter((e) => e.examiner_type === 'eksternal')
      .map((e) => e.name)
      .join(', ')

  const gradedRows = rows.filter((r) => r.total_hasil !== null && r.total_hasil !== undefined)
  const kompetenCount = gradedRows.filter((r) => r.status === 'kompeten').length
  const remedialCount = gradedRows.filter((r) => r.status === 'remedial').length
  const avgClassScore =
    gradedRows.length > 0
      ? (gradedRows.reduce((s, r) => s + Number(r.total_hasil), 0) / gradedRows.length).toFixed(2)
      : '-'

  const rubricHeaderCols = rubrics
    .map(
      (r) =>
        `<th style="border:1pt solid #334155;background-color:#f1f5f9;padding:5px;">B${r.item_order}</th>`
    )
    .join('')

  const bodyRowsHtml = rows
    .map((row, index) => {
      const rubricAvgRawCells = rubrics
        .map((r) => {
          const val = row.scores?.[r.id]?.raw_score
          return `<td class="text-center" style="border:1pt solid #334155;padding:5px;text-align:center;">${fmtScore(val)}</td>`
        })
        .join('')

      const statusLabel =
        row.status === 'kompeten'
          ? 'KOMPETEN'
          : row.status === 'remedial'
            ? 'REMEDIAL'
            : 'MENUNGGU'

      return `
        <tr>
          <td class="text-center" style="border:1pt solid #334155;padding:5px;text-align:center;">${index + 1}</td>
          <td class="text-center" style="border:1pt solid #334155;padding:5px;text-align:center;">${escapeHtml(row.nis)}</td>
          <td style="border:1pt solid #334155;padding:5px;"><strong>${escapeHtml(row.name)}</strong></td>
          <td class="text-center" style="border:1pt solid #334155;padding:5px;text-align:center;">${escapeHtml(row.class_name)}</td>
          ${rubricAvgRawCells}
          <td class="text-center font-bold" style="border:1pt solid #334155;padding:5px;text-align:center;font-weight:bold;background-color:#eef2ff;">
            ${fmtScore(row.total_hasil)}
          </td>
          <td class="text-center font-bold" style="border:1pt solid #334155;padding:5px;text-align:center;font-weight:bold;">
            ${statusLabel}
          </td>
        </tr>
      `
    })
    .join('')

  const rubricLegendHtml = rubrics
    .map((r) => `<span><strong>B${r.item_order}</strong>: ${escapeHtml(r.title)}</span>`)
    .join(' &bull; ')

  return `
    <div class="report-page">
      ${buildKopHeaderHtml(logoUrl, major)}

      <div class="doc-heading">
        <h2>LAPORAN REKAPITULASI NILAI UJIKOM (${escapeHtml(classInfo?.name || major)})</h2>
        <p>Rata-Rata Skor (0-100) &amp; Nilai Akhir Rata-Rata</p>
      </div>

      <table class="info-table">
        <tr>
          <td style="width:18%;"><strong>Kelas / Lingkup</strong></td>
          <td style="width:2%;">:</td>
          <td style="width:30%;">${escapeHtml(classInfo?.name || '-')} (${escapeHtml(major)})</td>
          <td style="width:20%;"><strong>Kompeten / Remedial</strong></td>
          <td style="width:2%;">:</td>
          <td style="width:28%;">${kompetenCount} Kompeten / ${remedialCount} Remedial (dari ${rows.length} Siswa)</td>
        </tr>
        <tr>
          <td><strong>Penguji Internal</strong></td>
          <td>:</td>
          <td>${escapeHtml(internalName || '-')}</td>
          <td><strong>Nilai Akhir Rata-Rata</strong></td>
          <td>:</td>
          <td><strong>${avgClassScore}</strong></td>
        </tr>
        <tr>
          <td><strong>Penguji Eksternal</strong></td>
          <td>:</td>
          <td colspan="4">${escapeHtml(externalName || '-')}</td>
        </tr>
      </table>

      <table class="data-table" style="font-size:8.5pt;width:100%;border-collapse:collapse;margin-bottom:10px;">
        <thead>
          <tr>
            <th style="width:26px;border:1pt solid #334155;background-color:#f1f5f9;padding:5px;">No</th>
            <th style="width:70px;border:1pt solid #334155;background-color:#f1f5f9;padding:5px;">NIS</th>
            <th style="border:1pt solid #334155;background-color:#f1f5f9;padding:5px;">Nama Siswa</th>
            <th style="width:75px;border:1pt solid #334155;background-color:#f1f5f9;padding:5px;">Kelas</th>
            ${rubricHeaderCols}
            <th style="width:85px;border:1pt solid #334155;background-color:#f1f5f9;padding:5px;">NILAI AKHIR RATA-RATA</th>
            <th style="width:75px;border:1pt solid #334155;background-color:#f1f5f9;padding:5px;">Status</th>
          </tr>
        </thead>
        <tbody>
          ${bodyRowsHtml}
        </tbody>
      </table>

      <table class="note-table" style="width:100%;border-collapse:collapse;margin-bottom:12px;">
        <tr>
          <td style="border:1pt solid #64748b;background-color:#f8fafc;padding:7px 10px;font-size:8pt;">
            <strong>Keterangan Butir Rubrik (Rata-Rata Skor 0-100):</strong><br/>
            ${rubricLegendHtml}
          </td>
        </tr>
      </table>

      ${buildSignatureBlockHtml({
        internalName,
        externalName,
        dateLocation: reportMeta.dateLocation,
      })}
    </div>
  `
}

function downloadWordDoc(innerBodyHtml, filename, orientation = 'portrait') {
  const fullHtml = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:w="urn:schemas-microsoft-com:office:word"
          xmlns="http://www.w3.org/TR/REC-html40">
    <head>
      <meta charset="utf-8" />
      <title>${escapeHtml(filename)}</title>
      <!--[if gte mso 9]>
      <xml>
        <w:WordDocument>
          <w:View>Print</w:View>
          <w:Zoom>100</w:Zoom>
          <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
      </xml>
      <![endif]-->
      <style>
        ${getDocumentStyles(orientation)}
      </style>
    </head>
    <body>
      <div class="Section1">
        ${innerBodyHtml}
      </div>
    </body>
    </html>
  `

  const blob = new Blob(['\ufeff', fullHtml], {
    type: 'application/msword;charset=utf-8',
  })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename.endsWith('.doc') ? filename : `${filename}.doc`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  setTimeout(() => URL.revokeObjectURL(url), 1500)
}

function openPdfPrintWindow(innerBodyHtml, title, orientation = 'portrait') {
  const printWin = window.open('', '_blank', 'width=980,height=800')
  if (!printWin) {
    window.alert('Izinkan pop-up pada browser untuk membuka/mengunduh dokumen PDF.')
    return
  }

  // @page { margin: 0mm } + <title>&nbsp;</title> secara otomatis menghilangkan
  // header tanggal/judul ("11/18/26... Bundel_Laporan...") dan footer ("about:blank") di Chrome/Edge
  const fullHtml = `
    <!doctype html>
    <html lang="id">
    <head>
      <meta charset="utf-8" />
      <title>&nbsp;</title>
      <style>
        ${getDocumentStyles(orientation)}
        @media screen {
          body {
            background: #e2e8f0;
            padding: 24px;
          }
          .report-page {
            background: #ffffff;
            max-width: ${orientation === 'landscape' ? '297mm' : '210mm'};
            margin: 0 auto 24px auto;
            padding: 14mm 16mm;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
            border-radius: 8px;
          }
          .print-banner {
            max-width: ${orientation === 'landscape' ? '297mm' : '210mm'};
            margin: 0 auto 16px auto;
            background: #0f172a;
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: Arial, sans-serif;
          }
          .print-btn {
            background: #4f46e5;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
          }
        }
        @media print {
          body {
            background: #ffffff;
            margin: 0;
            padding: 0;
          }
          .print-banner,
          .word-page-break {
            display: none !important;
          }
          .report-page {
            padding: 13mm 15mm;
            page-break-inside: avoid;
            break-inside: avoid;
          }
          .report-page:not(:last-child) {
            page-break-after: always !important;
            break-after: page !important;
          }
        }
      </style>
    </head>
    <body>
      <div class="print-banner">
        <span><strong>${escapeHtml(title)}</strong> — Pilih <em>"Save as PDF" / "Simpan sebagai PDF"</em> pada tujuan printer.</span>
        <button class="print-btn" onclick="window.print()">Simpan PDF / Cetak</button>
      </div>
      ${innerBodyHtml}
      <script>
        window.onload = function() {
          setTimeout(function() { window.print(); }, 350);
        };
      <\/script>
    </body>
    </html>
  `

  printWin.document.open()
  printWin.document.write(fullHtml)
  printWin.document.close()
}

export async function exportSingleStudentReport({
  row,
  rubrics,
  examiners,
  reportMeta,
  format = 'pdf',
}) {
  const logoUrl = await getLogoDataUrl()
  const html = buildSingleStudentSheetHtml({
    row,
    rubrics,
    examiners,
    logoUrl,
    reportMeta,
  })
  const safeName = `${row.nis}_${row.name}`.replace(/[^a-zA-Z0-9_-]+/g, '_')
  const title = `Laporan_Nilai_Ujikom_${safeName}`

  if (format === 'word') {
    downloadWordDoc(html, `${title}.doc`, 'portrait')
  } else {
    openPdfPrintWindow(html, title, 'portrait')
  }
}

export async function exportAllStudentsBundleReport({
  payload,
  reportMeta,
  format = 'pdf',
}) {
  const logoUrl = await getLogoDataUrl()
  const { class: classInfo, rubrics = [], examiners = [], rows = [] } = payload

  const pagesHtml = rows
    .map((row, idx) => {
      const sheet = buildSingleStudentSheetHtml({
        row,
        rubrics,
        examiners,
        logoUrl,
        reportMeta,
      })
      const isLast = idx === rows.length - 1
      if (isLast || format !== 'word') return sheet
      return `${sheet}<br clear="all" class="word-page-break" style="mso-special-character:line-break;page-break-before:always" />`
    })
    .join('\n')

  const safeClass = (classInfo?.name || 'Semua_Kelas').replace(/[^a-zA-Z0-9_-]+/g, '_')
  const title = `Bundel_Laporan_Nilai_Siswa_${safeClass}`

  if (format === 'word') {
    downloadWordDoc(pagesHtml, `${title}.doc`, 'portrait')
  } else {
    openPdfPrintWindow(pagesHtml, title, 'portrait')
  }
}

export async function exportClassSummaryDocument({
  payload,
  reportMeta,
  format = 'pdf',
}) {
  const logoUrl = await getLogoDataUrl()
  const html = buildClassSummaryReportHtml({
    payload,
    logoUrl,
    reportMeta,
  })
  const safeClass = (payload.class?.name || 'Rekap_Kelas').replace(/[^a-zA-Z0-9_-]+/g, '_')
  const title = `Laporan_Rekapitulasi_Ujikom_${safeClass}`

  if (format === 'word') {
    downloadWordDoc(html, `${title}.doc`, 'landscape')
  } else {
    openPdfPrintWindow(html, title, 'landscape')
  }
}
