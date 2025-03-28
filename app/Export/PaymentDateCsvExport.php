<?php

declare(strict_types=1);

namespace App\Export;

use Generator;
use Spatie\SimpleExcel\SimpleExcelWriter;

class PaymentDateCsvExport
{
    private CONST HEADERS = ['month', 'salary_payment_date', 'bonus_payment_date'];

    public function generateCsv(string $path, Generator $data)
    {
        SimpleExcelWriter::create($path)
        ->addHeader(self::HEADERS) // Headers are derived from delivered data, so this is not really needed. We enforce it anyways.
        ->addRows($data);
    }
}