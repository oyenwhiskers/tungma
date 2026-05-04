<?php

namespace App\Exports;

use App\Models\ECustomer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TaxEntityExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        // Fallbacks for TaxClassification based on IdentityType or just using '1'
        // According to template: MyKAD = 1, etc. But if the data has customer_type we would ideally map that.
        // I will use some sensible defaults for classification if there's no direct map.
        $taxClass = '1';

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
            $eCustomer->identity_type ?? 'MyKAD',
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
}
