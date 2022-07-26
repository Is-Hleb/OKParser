<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Parser;
use App\Models\ParserType;
use Illuminate\Http\Request;

class ParserController extends Controller
{
    public function create(Request $request) {
        $name = $request->input('name');
        $token = $request->input("token");

        $keys = array_keys($request->all());
        $typesIds = [];
        foreach ($keys as $key) {
            if(str_contains($key, 'type')) {
                if($request->input($key) == 'on') {
                    $typesIds[] = explode('_', $key)[1];
                }
            }
        }

        $parser = Parser::create([
            'name' => $name,
            'token' => $token
        ]);

        $parser->types()->attach($typesIds);
        $parser->save();
        return redirect()->back();
    }
}
