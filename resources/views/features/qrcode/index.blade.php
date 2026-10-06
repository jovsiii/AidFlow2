@extends('features.layout')

@section('title', 'QR Code Generator - AidFlow')
@section('page-title', 'QR Code Generator')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Generate QR Code</h2>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <form id="qrForm" class="space-y-4">
                <div>
                    <label for="qrType" class="block text-sm font-medium text-gray-700 mb-1">QR Code Type</label>
                    <select id="qrType" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="tent">Tent Code</option>
                        <option value="food">Food Pack</option>
                    </select>
                </div>

                <div id="tentField" class="space-y-2">
                    <label for="tentCodeSelect" class="block text-sm font-medium text-gray-700 mb-1">Tent Code</label>
                    <select id="tentCodeSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></select>
                </div>

                <div id="foodField" class="space-y-2 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Relief Pack Number</label>
                    <div id="foodPackDisplay" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-900 font-medium">Relief Pack: #1 to #10</div>
                </div>

                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                    <i class="fas fa-qrcode mr-2"></i> Generate &amp; Download PDF
                </button>
            </form>

            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 flex flex-col items-center justify-center min-h-[320px]">
                <div id="qrCode" class="bg-white p-4 rounded-lg shadow-sm min-h-[220px] min-w-[220px] flex items-center justify-center"></div>
                <p id="qrPreviewLabel" class="mt-4 text-lg font-semibold text-gray-900">Tent Code</p>
                <p id="qrOutput" class="mt-2 text-sm text-gray-600 break-all text-center">No QR generated yet</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script type="module">
    import { barangayList } from '/js/barangayList.js';

    const qrType = document.getElementById('qrType');
    const tentField = document.getElementById('tentField');
    const foodField = document.getElementById('foodField');
    const tentCodeSelect = document.getElementById('tentCodeSelect');
    const foodPackDisplay = document.getElementById('foodPackDisplay');
    const qrPreviewLabel = document.getElementById('qrPreviewLabel');
    const qrOutput = document.getElementById('qrOutput');
    const qrCode = document.getElementById('qrCode');
    const PACK_COUNTER_KEY = 'aidflow_relief_pack_counter';
    const MAX_RELIEF_PACKS = 10;

    function buildTentOptions() {
        const tentCodes = [];

        barangayList.forEach((barangay) => {
            Object.keys(barangay.tents).forEach((tentCode) => {
                tentCodes.push(tentCode);
            });
        });

        tentCodeSelect.innerHTML = tentCodes.map((tentCode) => `
            <option value="${tentCode}">${tentCode}</option>
        `).join('');

        if (tentCodes.length) {
            tentCodeSelect.value = tentCodes[0];
        }
    }

    function getNextFoodPackNumber() {
        const current = Number(localStorage.getItem(PACK_COUNTER_KEY) || '1');
        const next = Number.isFinite(current) && current > 0 ? current : 1;
        localStorage.setItem(PACK_COUNTER_KEY, String(next + 1));
        return next;
    }

    function rangeLabel() {
        return `Relief Pack: #1 to #${MAX_RELIEF_PACKS}`;
    }

    function updateQrInputState() {
        const isFoodPack = qrType.value === 'food';

        tentField.classList.toggle('hidden', isFoodPack);
        foodField.classList.toggle('hidden', !isFoodPack);
        qrPreviewLabel.textContent = isFoodPack ? 'Food Pack' : 'Tent Code';

        if (isFoodPack) {
            foodPackDisplay.textContent = rangeLabel();
        }
    }

    function generateQrCodeForText(text) {
        const qr = qrcode(0, 'M');
        qr.addData(text);
        qr.make();
        qrCode.innerHTML = qr.createImgTag(10, 10);
        qrOutput.textContent = text;
    }

    function generateQrCode() {
        const isFoodPack = qrType.value === 'food';

        const qrText = isFoodPack
            ? '#1'
            : `${tentCodeSelect.value}`;

        generateQrCodeForText(qrText);
    }

    function downloadReliefPackPdf() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ unit: 'mm', format: 'a4' });

        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const marginX = 16;
        const marginY = 20;
        const cellW = 80;
        const cellH = 58;
        const qrSize = 28;
        const cols = 2;
        const rowsPerPage = 5;

        doc.setFillColor(255, 248, 248);
        doc.rect(0, 0, pageWidth, 18, 'F');
        doc.setTextColor(138, 28, 28);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        doc.text('AidFlow Relief Pack QR Sheet', pageWidth / 2, 11, { align: 'center' });

        let pagePackIndex = 1;

        for (let pack = 1; pack <= MAX_RELIEF_PACKS; pack++) {
            const rowIndex = Math.floor((pack - 1) / cols) % rowsPerPage;
            const colIndex = (pack - 1) % cols;
            const x = marginX + colIndex * cellW;
            const y = marginY + rowIndex * cellH + 10;

            const qrText = `#${pack}`;
            const qr = qrcode(0, 'M');
            qr.addData(qrText);
            qr.make();
            const qrDataUrl = qr.createDataURL(10, 0);

            doc.addImage(qrDataUrl, 'PNG', x + 10, y, qrSize, qrSize);
            doc.setTextColor(30, 41, 59);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(10);
            doc.text(`Relief Pack`, x + 42, y + 8);
            doc.setFont('helvetica', 'normal');
            doc.text(`#${pack}`, x + 42, y + 14);

            if ((pack % (cols * rowsPerPage)) === 0 && pack < MAX_RELIEF_PACKS) {
                doc.addPage();
                doc.setFillColor(255, 248, 248);
                doc.rect(0, 0, pageWidth, 18, 'F');
                doc.setTextColor(138, 28, 28);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(12);
                doc.text('AidFlow Relief Pack QR Sheet', pageWidth / 2, 11, { align: 'center' });
                pagePackIndex = pack + 1;
            }
        }

        doc.save('relief-pack-qr-codes.pdf');
        generateQrCodeForText('#1');
    }

    qrType.addEventListener('change', () => {
        updateQrInputState();
        generateQrCode();
    });

    tentCodeSelect.addEventListener('change', () => {
        if (qrType.value !== 'food') {
            generateQrCode();
        }
    });

    document.getElementById('qrForm').addEventListener('submit', (event) => {
        event.preventDefault();
        updateQrInputState();

        if (qrType.value === 'food') {
            downloadReliefPackPdf();
            return;
        }

        generateQrCode();
    });

    buildTentOptions();
    updateQrInputState();
    generateQrCode();
</script>
@endsection
