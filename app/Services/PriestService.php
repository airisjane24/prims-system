<?php

namespace App\Services;

use App\Constant\MyConstant;
use App\Models\Notification;
use App\Models\Priest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PriestService
{
    private $validator;

    public function __construct($validator)
    {
        $this->validator = $validator;
    }

    /**
     * Store a new priest
     */
    public function store($request)
    {
        $validator = Validator::make($request->all(), $this->validator->priestValidator());

        if ($validator->fails()) {
            return [
                'error_code' => MyConstant::FAILED_CODE,
                'status_code' => MyConstant::BAD_REQUEST,
                'message' => $validator->errors()->first(),
            ];
        }

        try {
            $data = $validator->validated();

            // Handle image upload
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileName = 'priest_' . time() . '.' . $file->getClientOriginalExtension();
                // store in storage/app/public/priests
                $file->storeAs('priests', $fileName, 'public');
                $data['image'] = $fileName;
            }

            $priest = Priest::create($data);

            // Create notification
            Notification::create([
                'type' => 'Priest',
                'message' => 'A new priest has been added by ' . Auth::user()->name,
                'is_read' => 0,
            ]);

            session()->flash('success', 'Priest created successfully');
            return [
                'error_code' => MyConstant::SUCCESS_CODE,
                'status_code' => MyConstant::OK,
                'message' => 'Priest created successfully',
            ];
        } catch (QueryException $e) {
            session()->flash('error', 'Internal server error');
            return [
                'error_code' => MyConstant::FAILED_CODE,
                'status_code' => MyConstant::BAD_REQUEST,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing priest
     */
    public function update($request, $id)
    {
        $validator = Validator::make($request->all(), $this->validator->priestValidator());

        if ($validator->fails()) {
            return [
                'error_code' => MyConstant::FAILED_CODE,
                'status_code' => MyConstant::BAD_REQUEST,
                'message' => $validator->errors()->first(),
            ];
        }

        try {
            $priest = Priest::find($id);

            if (!$priest) {
                return [
                    'error_code' => MyConstant::FAILED_CODE,
                    'status_code' => MyConstant::NOT_FOUND,
                    'message' => 'Priest not found',
                ];
            }

            $data = $validator->validated();

            // Handle image update
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($priest->image && Storage::disk('public')->exists('priests/' . $priest->image)) {
                    Storage::disk('public')->delete('priests/' . $priest->image);
                }

                // Store new image
                $file = $request->file('image');
                $fileName = 'priest_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('priests', $fileName, 'public');
                $data['image'] = $fileName;
            }

            $priest->update($data);

            session()->flash('success', 'Priest updated successfully');
            return [
                'error_code' => MyConstant::SUCCESS_CODE,
                'status_code' => MyConstant::OK,
                'message' => 'Priest updated successfully',
            ];
        } catch (QueryException $e) {
            session()->flash('error', 'Internal server error');
            return [
                'error_code' => MyConstant::FAILED_CODE,
                'status_code' => MyConstant::BAD_REQUEST,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a priest
     */
    public function destroy($id)
    {
        try {
            $priest = Priest::find($id);

            if (!$priest) {
                return [
                    'error_code' => MyConstant::FAILED_CODE,
                    'status_code' => MyConstant::NOT_FOUND,
                    'message' => 'Priest not found',
                ];
            }

            // Delete image from storage
            if ($priest->image && Storage::disk('public')->exists('priests/' . $priest->image)) {
                Storage::disk('public')->delete('priests/' . $priest->image);
            }

            $priest->delete();

            session()->flash('success', 'Priest deleted successfully');
            return [
                'error_code' => MyConstant::SUCCESS_CODE,
                'status_code' => MyConstant::OK,
                'message' => 'Priest deleted successfully',
            ];
        } catch (QueryException $e) {
            return [
                'error_code' => MyConstant::FAILED_CODE,
                'status_code' => MyConstant::BAD_REQUEST,
                'message' => $e->getMessage(),
            ];
        }
    }
}
