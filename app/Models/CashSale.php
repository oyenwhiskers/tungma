<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'doc_date' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(CashSaleDetail::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function generateXml()
    {
        $xml = new \SimpleXMLElement('<CashSale></CashSale>');
        $xml->addAttribute('DocNo', $this->doc_no);
        $xml->addAttribute('ImportAction', $this->import_action);

        $xml->addChild('DocDate', $this->doc_date->format('Y-m-d'));
        $xml->addChild('DebtorCode', $this->debtor_code);
        $xml->addChild('DebtorName', $this->debtor_name ?? '');
        $xml->addChild('Description', $this->description ?? 'null');
        $xml->addChild('DisplayTerm', $this->display_term);
        $xml->addChild('Ref', $this->ref ?? 'null');
        $xml->addChild('SalesAgent', $this->sales_agent ?? 'null');

        foreach ($this->details as $detail) {
            $child = $xml->addChild('Detail');
            $child->addChild('ItemCode', $detail->item_code);
            $child->addChild('UOM', $detail->uom);
            $child->addChild('Qty', number_format($detail->qty, 6, '.', ''));
            $child->addChild('UnitPrice', number_format($detail->unit_price, 6, '.', ''));
            $child->addChild('Location', $detail->location ?? 'null');
            $child->addChild('Description', $detail->description ?? 'null');
            $child->addChild('TaxType', $detail->tax_type ?? 'null');
            $child->addChild('Amount', number_format($detail->amount, 2, '.', ''));
        }

        return $xml->asXML();
    }
}
