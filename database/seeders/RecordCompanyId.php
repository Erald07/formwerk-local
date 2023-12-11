<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Record;

class RecordCompanyId extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $records = Record::with("form")->get();
        foreach($records as $rec) {
            $record = Record::firstWhere("id", $rec->id);
            $record->company_id = $rec->form->company_id;
            $record->save();
        }
    }
}
