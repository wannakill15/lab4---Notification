<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use App\Models\Comment;

class PostController extends Controller
{
    public function store(Request $request) {
        $request->validate([
            'content' => 'required|max:255',
        ]);
    
        $post = new Post();
        $post->content = $request->content;
        $post->user_id = Auth::id();
        $post->save();
    
        return response()->json($post, 201);
    }
        
    public function index() {
        // Ensure 'comments.user' relationship is included
        $posts = Post::with('user', 'comments.user')->latest()->get(); 
        
        foreach ($posts as $post) {
            // Ensure that the current user's like status is retrieved correctly
            $post->userHasLiked = $post->likes()->where('user_id', Auth::id())->exists();
        }
    
        return response()->json($posts);
    }
        
    public function destroy($id) {
        // Find the post by ID or fail with a 404 error if not found
        $post = Post::findOrFail($id);
    
        // Check if the authenticated user is the owner of the post
        if ($post->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        // Delete the post
        $post->delete();
    
        // Return a success message
        return response()->json(['message' => 'Post deleted successfully']);
    }
    
    public function likePost(Post $post)
    {
        // Check if the user has already liked the post
        $existingLike = $post->likes()->where('user_id', Auth::id())->first();
    
        if ($existingLike) {
            // If the like exists, remove it (unlike the post)
            $existingLike->delete();
            $post->decrement('likes_count'); // Decrement the likes_count field
            return response()->json(['message' => 'Post unliked']);
        } else {
            // If not liked yet, like the post
            $post->likes()->create(['user_id' => Auth::id()]);
            $post->increment('likes_count'); // Increment the likes_count field
            return response()->json(['message' => 'Post liked']);
        }
    }

    public function addComment(Request $request, Post $post)
    {
        $request->validate([
            'comment' => 'required|string|max:255',
        ]);
    
        $comment = new Comment();
        $comment->comment = $request->comment;
        $comment->user_id = Auth::id();  // The user adding the comment
        $comment->post_id = $post->id;   // The post the comment belongs to
        $comment->save();
    
        return response()->json($comment, 201);
    }
        
}