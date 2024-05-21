<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\UserResourceFavorite;

use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use App\Http\Resources\UserFavoritesResource;
use PHPUnit\Util\Json;

class UserResourceFavoritesController extends Controller
{
    use ApiResponses;

    /**
    * Get user favorites from the db.
    *
    * @return \Illuminate\Http\Response
    */
    public function getFavorites($user_id)
    {
        try {

            $user = User::findOrFail($user_id);
            $favorites = UserResourceFavorite::whereUserId($user->id)->whereIsFavorite(true)->get();

            $transform = UserFavoritesResource::collection($favorites);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }

    /**
    * Add a new favorites to the db.
    *
    * @return \Illuminate\Http\Response
    */
    public function addFavorites(Request $request)
    {
        try {
            $data = $request->json()->all();

            $user = User::findOrFail($data['user_id']);

            $findResource = UserResourceFavorite::whereUserId($user->id)->whereResourceId($data['resource_id'])->first();
            if (isset($findResource)){
                $findResource->is_favorite = true;
                $findResource->save();
            }else{
                $user_favorite = new UserResourceFavorite;
                $user_favorite->user_id = $user->id;
                $user_favorite->resource_id = $data['resource_id'];
                $user_favorite->is_favorite = true;
                $user_favorite->save();
            }

            $favorites = UserResourceFavorite::whereUserId($user->id)->whereIsFavorite(true)->get();

            $transform = UserFavoritesResource::collection($favorites);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }

    /**
    * Remove favorite from the db
    *
    * @return \Illuminate\Http\Response
    */
    public function removeFavorite(Request $request)
    {

        try {
            $data = $request->json()->all();

            $user = User::findOrFail($data['user_id']);
            $user_favorite = UserResourceFavorite::whereUserId($user->id)->whereResourceId($data['resource_id'])->first();
            $user_favorite->is_favorite = false;
            $user_favorite->save();

            $favorites = UserResourceFavorite::whereUserId($user->id)->whereIsFavorite(true)->get();

            $transform = UserFavoritesResource::collection($favorites);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }


}

