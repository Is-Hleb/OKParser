<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property Parser $parser
 * @property string $table_name
 * @property int $parser_id
 * @property int $rows_count
 * @property string $output_path
 */
class ParserTask extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function parser() {
        return $this->belongsTo(Parser::class);
    }
}
