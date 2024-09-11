<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use App\Models\Filter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class FilterController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // validate request info
            $validateFields = Validator::make($request->all(), [
                'id_users' => 'exists:users,id'
            ]);

            if($validateFields->fails()){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation Error',
                    'errors' => $validateFields->errors()
                ], 422);
            }

            // create
            $user = Filter::create([
                'id_users' => $request->id_users,
                'name' => $request->name,
                'keyword' => $request->keyword,
                'date' => $request->date,
                'category' => $request->category,
                'source' => $request->source,
                'author' => $request->author,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Filter Created Successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $filter = Filter::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Filter Retrieved Successfully',
                'data' => $filter
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // validate request info
            $validateFields = Validator::make($request->all(), [
                'id_users' => 'exists:users,id'
            ]);

            if($validateFields->fails()){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation Error',
                    'errors' => $validateFields->errors()
                ], 422);
            }

            // update
            $filter = Filter::findOrFail($id);
            $filter->id_users = $request->id_users;
            $filter->name = $request->name;
            $filter->keyword = $request->keyword;
            $filter->date = $request->date;
            $filter->category = $request->category;
            $filter->source = $request->source;
            $filter->author = $request->author;
    
            $filter->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Filter Updated Successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            Filter::destroy($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Filter Deleted Successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function filtersByUserId(string $id_users) {
        try {
            $data = Filter::select(
                    'id',
                    'id_users',
                    'name',
                    'keyword',
                    'date',
                    'category',
                    'source',
                    'author',
                )->where('id_users', $id_users)
                ->get()->all();

            return response()->json([
                'status' => 'success',
                'message' => 'Filters Retrieved Successfully',
                'data' => $data
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function batchUpdate(Request $request) {
        try {
            $filters = $request->all();
            foreach ($filters as $filter) {
                if (isset($filter['delete'])) {
                    Filter::destroy($filter['id']);
                } else {
                    $this->quickFormUpdate($filter['id'], $filter);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Filters Updated Successfully',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function quickFormUpdate($id, $data) {
        $filter = Filter::find($id);
        $filter->id_users = $data['id_users'];
        $filter->name = empty($data['filterName']) ? $data['name'] : $data['filterName'];
        $filter->keyword = $data['keyword'];
        $filter->date = $data['date'];
        $filter->category = $data['category'];
        $filter->source = $data['source'];
        $filter->author = $data['author'];
        $filter->save();
    }
}
