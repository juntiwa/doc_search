<?php

namespace App\Models;

use App\Traits\CSVLoadable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Letterunit extends Model
{
    use HasFactory,CSVLoadable;

    protected $fillable = [
        'unitid',
        'unitname',
    ];

    public function document()
    {
        //letterunit belong to document
        return $this->belongsTo(Letterreg::class, 'unitid', 'regfrom');
    }

    public function description()
    {
        //letterunit belong to description
        return $this->belongsTo(Lettersend::class, 'unitid', 'sendtoid');
    }
}
