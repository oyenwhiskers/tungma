<?php

namespace App\Exports;

use App\Models\Bill;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AutoCountExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $date;
    protected $companyId;

    public function __construct($date, $companyId = null)
    {
        $this->date = $date;
        $this->companyId = $companyId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Bill::with(['company', 'cashSale'])
            ->whereDate('date', $this->date);

        if ($this->companyId && $this->companyId !== 'all') {
            $query->where('company_id', $this->companyId);
        }

        return $query->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'DocNo',
            'DocDate',
            'CompanyName',
            'DebtorCode',
            'DetailDescription',
            'AccNo',
            'SubTotal'
        ];
    }

    /**
     * @var Bill $bill
     */
    public function map($bill): array
    {
        $cashSale = $bill->cashSale;

        // Parse the JSON description
        $descriptionString = 'Cash Sale';
        if (!empty($bill->description)) {
            $parsedDesc = json_decode($bill->description, true);
            if (is_array($parsedDesc)) {
                $descItems = [];
                foreach ($parsedDesc as $item) {
                    if (isset($item['product']) && isset($item['quantity'])) {
                        $descItems[] = $item['product'] . ' (' . $item['quantity'] . ' Pcs)';
                    }
                }
                if (!empty($descItems)) {
                    $descriptionString = implode(', ', $descItems);
                } else {
                    $descriptionString = $bill->description; // fallback
                }
            } else {
                $descriptionString = $bill->description;
            }
        }

        // Determine Debtor Code
        $debtorCode = '300-CASH'; // Default safe fallback
        if (!empty($bill->debtor_code)) {
            $debtorCode = $bill->debtor_code;
        } elseif (isset($bill->customer) && !empty($bill->customer->debtor_code)) {
            $debtorCode = $bill->customer->debtor_code;
        } elseif ($cashSale && !empty($cashSale->debtor_code)) {
            $debtorCode = $cashSale->debtor_code;
        }

        return [
            $bill->bill_code,
            $bill->date->format('Y-m-d'),
            $bill->sender_name ?? optional($bill->company)->name ?? 'N/A',
            $debtorCode,
            $descriptionString,
            '', // AccNo empty as requested
            number_format($bill->amount, 2, '.', '')
        ];
    }
}
