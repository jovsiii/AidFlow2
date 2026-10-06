<?php

it('renders a qr generator with tent and food pack options', function () {
    $html = view('features.qrcode.index')->render();

    expect($html)
        ->toContain('Generate QR Code')
        ->toContain('Tent Code')
        ->toContain('Food Pack')
        ->toContain('Download All Tent QR Codes as PDF')
        ->toContain('downloadTentQrPdf();');
});
