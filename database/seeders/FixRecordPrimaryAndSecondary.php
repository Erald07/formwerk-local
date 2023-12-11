<?php

namespace Database\Seeders;

use App\Models\Record;
use Illuminate\Database\Seeder;

class FixRecordPrimaryAndSecondary extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        $records = Record::with('form')->get();
        foreach($records as $record) {
            if(isset($record->form)) {
                $form = $record->form->toArray();
                $options = json_decode($form['options'], true);
                $values = json_decode($record->fields, true);
                if(!empty($options['key-fields-primary'])) {
                    // primary key
                    $record->primary_field_id = $options['key-fields-primary'];
                    if(!empty($values[$options['key-fields-primary']])) {
                        $record->primary_field_value = $values[$options['key-fields-primary']];
                    }
                }
                if(!empty($options["key-fields-secondary"])) {
                    // secondary key
                    $record->secondary_field_id = $options['key-fields-secondary'];
                    if (!empty($values[$options['key-fields-secondary']])) {
                        $record->secondary_field_value = $values[$options['key-fields-secondary']];
                    }
                }
                if($record->isDirty()) {
                    $record->save();
                }
            }
        }
    }
}
