<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Partner;
use App\Models\ProposerUnit;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();
        
        foreach ($users as $user) {
            if ($user->hasRole('client')) {
                // If user is client, create or find Partner
                $partnerName = $user->nama_mitra ?: ($user->name . ' (Mitra)');
                
                $partner = Partner::firstOrCreate(
                    ['name' => $partnerName]
                );
                
                $user->partner_id = $partner->id;
                $user->save();
            }
            
            if ($user->hasRole('unit_pengusul')) {
                // If user is unit pengusul, create or find ProposerUnit
                $unitName = $user->jabatan ?: ($user->name . ' (Unit)');
                
                $unit = ProposerUnit::firstOrCreate(
                    ['name' => $unitName]
                );
                
                $user->proposer_unit_id = $unit->id;
                $user->save();
            }
        }
    }
}
