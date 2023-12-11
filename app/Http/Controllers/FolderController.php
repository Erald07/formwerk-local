<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Form;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    //

    public function create(Request $request)
    {

        $data = $request->all();
        $data['level'] = 0;
        if($data['parent_id']) {
            $parent = Folder::findOrFail($data['parent_id']);
            $data['level'] = $parent->level += 1;
        }
        $folder = Folder::create($data);

        return $folder;
    }

    public function update(Request $request, $id)
    {

        $data = $request->all();
        $folder = Folder::findOrFail($id);

        $data['level'] = 0;
        if ($data['parent_id']) {
            $parent = Folder::findOrFail($data['parent_id']);
            $data['level'] = $parent->level += 1;
        }
        $folder->update($data);
        return $folder;
    }

    public function updateParent(Request $request, $id, $parentId = 0)
    {   
        $data = [];
        $parentId = 1 * $parentId;
        $folder = Folder::findOrFail($id);
        if($folder->parent_id !== $parentId) {
            if($parentId) {
                $parent = Folder::findOrFail($parentId);
                $data['level'] = $parent->level += 1;
                $data['parent_id'] = $parentId;
            } else {
                 $data['level'] = 0;
                 $data['parent_id'] = null;
            }
            $folder->update($data);
        }
        return $folder;
    }

    public function delete(Request $request, $id){
        $folder = Folder::findOrFail($id);
        $folder_id = null;
        if ($folder->parent_id) {
            $parent = Folder::findOrFail($folder->parent_id);
            $folder_id = $parent->id;
        }
        Form::where('folder_id', $id)->update(['folder_id' => $folder_id]);
        Folder::where('parent_id', $id)->update([
            'parent_id' => $folder_id
        ]);
        $folder->delete();
        return $folder;
    }
}
