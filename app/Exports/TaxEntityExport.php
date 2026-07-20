<?php

namespace App\Exports;

use App\Models\ECustomer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class TaxEntityExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithCustomValueBinder
{
    protected $eCustomers;

    public function __construct($eCustomers = null)
    {
        $this->eCustomers = $eCustomers;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        if ($this->eCustomers) {
            return $this->eCustomers;
        }
        return ECustomer::with('bill')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'TIN',
            'IdentityNo',
            'Name',
            'IdentityType',
            'TaxClassification',
            'GSTRegisterNo',
            'SSTRegisterNo',
            'TourismTaxRegisterNo',
            'MSICCode',
            'BusinessActivityDesc',
            'DebtorCode',
            'CreditorCode',
            'TradeName',
            'Address',
            'PostCode',
            'Phone',
            'EmailAddress',
            'City',
            'CountryCode',
            'StateCode'
        ];
    }

    /**
     * @var ECustomer $eCustomer
     */
    public function map($eCustomer): array
    {
        // Dynamically map TaxClassification and IdentityType based on customer_type
        $customerType = strtolower($eCustomer->customer_type ?? '');
        if ($customerType === 'business' || $customerType === 'corporate') {
            $taxClass = '1';
            $identityType = '';
        } elseif ($customerType === 'government') {
            $taxClass = '2';
            $identityType = '';
        } else {
            $taxClass = '0';
            $identityType = $eCustomer->identity_type ?? 'MyKAD';
        }

        $address = $eCustomer->address;
        if(is_array($address) || (substr($address, 0, 1) === '[' || substr($address, 0, 1) === '{')) {
           $address = json_decode($address, true);
           if(is_array($address)) {
               $address = implode(', ', array_filter($address));
           }
        }

        return [
            $eCustomer->tin_number ?? '',
            $eCustomer->customer_ic ?: $eCustomer->business_reg_number,
            $eCustomer->customer_name ?? '',
            $identityType,
            $taxClass,
            '', // GSTRegisterNo
            $eCustomer->sst_reg_number ?? '',
            '', // TourismTaxRegisterNo
            '00000', // MSICCode (Hardcoded as requested)
            'NOT APPLICABLE', // BusinessActivityDesc (Hardcoded as requested)
            '', // DebtorCode - to be auto-assigned or customized later
            '', // CreditorCode
            '', // TradeName (or company_name if exists)
            $address ?? '',
            $eCustomer->postcode ?? '',
            $eCustomer->contact_number ?? '',
            $eCustomer->email_address ?? '',
            $eCustomer->city ?? '',
            $eCustomer->country_code ?? 'MYS',
            $eCustomer->state_code ?? ''
        ];
    }

    /**
     * Bind cell values explicitly as strings for TIN and IdentityNo columns.
     */
    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() === 'A' || $cell->getColumn() === 'B') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
