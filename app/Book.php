<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $primaryKey = 'b_id';

    protected $fillable = [
        'book_name', 'author', 'cover_image',
    ];

    public function user()
    {
        return $this->belongsTo('App\User','u_id','u_id');
    }

    public function getCoverImageAttribute($coverImage)
    {
        $uploadFolder = \Config::get('books.upload_folder');
        return \URL::to('/')."/storage/".$uploadFolder."/".$coverImage;
    }
}
