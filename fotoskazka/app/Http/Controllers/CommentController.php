<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StorePhotoCommentRequest;
use App\Models\Photo;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function storeProject(StoreCommentRequest $request, Project $project): RedirectResponse
    {
        $project->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return redirect()->route('cabinet.project', $project);
    }

    public function storePhoto(StorePhotoCommentRequest $request, Photo $photo): RedirectResponse
    {
        $photo->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return redirect()
            ->route('cabinet.album', $photo->album)
            ->withFragment('photo-'.$photo->id);
    }
}
