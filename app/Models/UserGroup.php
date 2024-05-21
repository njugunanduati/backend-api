<?php

namespace App\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;


class UserGroup extends Model
{

  use SoftDeletes;

  protected $table = 'user_groups';
  public $timestamps = true;

  protected $fillable = [
    'name',
    'user_id',
    'group_id',
    'active',
    'price',
    'paused',
    'meeting_day',
    'meeting_time',
    'time_zone',
    'meeting_url',
    'status',
  ];

  public function setNameAttribute($value)
  {
      $this->attributes['name'] = trimSpecial(strip_tags($value));
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function group()
  {
    return $this->belongsTo(Group::class, 'group_id');
  }

  public function getPauseDetails()
  {
    return $this->hasOne(GroupCoachingPausedSession::class, 'group_id');
  }

  public function members()
  {

    $id = $this->id;

    return DB::table('users')
      ->join('member_group_lesson', 'users.id', '=', 'member_group_lesson.user_id')
      ->select('users.first_name', 'users.last_name', 'users.email')
      ->where(function ($a) use ($id) {
        $a->where('member_group_lesson.group_id', $id);
      })->where(function ($b) {
        $b->where('member_group_lesson.user_id', '!=', DB::raw('member_group_lesson.invited_by'));
      })->groupBy('member_group_lesson.user_id')->get();
  }

  public function users()
  {

    $id = $this->id;

    return DB::table('users')
      ->join('member_group_lesson', 'users.id', '=', 'member_group_lesson.user_id')
      ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
      ->where(function ($a) use ($id) {
        $a->where('member_group_lesson.group_id', $id);
      })->groupBy('member_group_lesson.user_id')->get();
  }

  public function lessonaccess(){
    return $this->hasMany(LessonAccess::class);
  }
  public function lessons()
  {

    $id = $this->id;
    $coach_id = $this->user_id;

    if ($this->group_id == 29) {

      return DB::table('custom_group_lessons')
        ->join('lessons', 'lessons.id', '=', 'custom_group_lessons.lesson_id')
        ->select(
          'custom_group_lessons.id',
          'custom_group_lessons.group_id',
          'custom_group_lessons.user_group_id',
          'custom_group_lessons.lesson_id',
          'custom_group_lessons.lesson_order',
          'custom_group_lessons.lesson_length',
          'custom_group_lessons.created_at',
          'custom_group_lessons.updated_at'
        )->where('custom_group_lessons.user_group_id', $id)->orderBy('custom_group_lessons.lesson_order')->get();
    } else {
      return DB::table('lessons')
        ->join('member_group_lesson', 'lessons.id', '=', 'member_group_lesson.lesson_id')
        ->select(
          'lessons.id',
          'lessons.title',
          'member_group_lesson.lesson_length',
          'member_group_lesson.created_at'
        )->where(function ($a) use ($coach_id) {
          $a->where('member_group_lesson.user_id', $coach_id);
        })->where(function ($b) use ($coach_id) {
          $b->where('member_group_lesson.invited_by', $coach_id);
        })->where(function ($c) use ($id) {
          $c->where('member_group_lesson.group_id', $id);
        })->groupBy('member_group_lesson.lesson_id')
        ->orderBy('lessons.next_lesson')->get();
    }
  }

  public function membergrouplessons()
  {

    $id = $this->id;
    $coach_id = $this->user_id;

    return DB::table('lessons')
      ->join('member_group_lesson', 'lessons.id', '=', 'member_group_lesson.lesson_id')
      ->select(
        'member_group_lesson.id',
        'member_group_lesson.lesson_id',
        'member_group_lesson.group_id',
        'member_group_lesson.lesson_access',
        'member_group_lesson.lesson_length',
        'member_group_lesson.lesson_order'
      )->where(function ($a) use ($coach_id) {
        $a->where('member_group_lesson.user_id', $coach_id);
      })->where(function ($b) use ($coach_id) {
        $b->where('member_group_lesson.invited_by', $coach_id);
      })->where(function ($c) use ($id) {
        $c->where('member_group_lesson.group_id', $id);
      })->groupBy('member_group_lesson.lesson_id')
      ->orderBy('member_group_lesson.lesson_order')->get();
  }

  public function customlessons()
  {

    $id = $this->id;

    return DB::table('member_group_lesson')
      ->join('lessons', 'lessons.id', '=', 'member_group_lesson.lesson_id')
      ->join('user_groups', 'user_groups.id', '=', 'member_group_lesson.group_id')
      ->select(
        'lessons.id',
        'lessons.title',
        'user_groups.id as user_group_id',
        'user_groups.group_id',
        'member_group_lesson.lesson_order',
        'member_group_lesson.lesson_length',
        'member_group_lesson.created_at',
        'member_group_lesson.updated_at',
        'lessons.free_lesson',
        'lessons.full_desc',
        'lessons.lesson_img',
        'lessons.lesson_video',
        'lessons.next_lesson',
        'lessons.owner',
        'lessons.price',
        'lessons.published',
        'lessons.quiz_url',
        'lessons.short_desc',
        'lessons.slug'
      )->where('user_groups.id', $id)
      ->groupBy('member_group_lesson.lesson_id')
      ->orderBy('member_group_lesson.lesson_order')->get();
  }


  public function nextlesson()
  {

    $timezone = trim(str_replace(" ", "", $this->time_zone));

    $meeting_date = Carbon::now($timezone);

    $lessons = $this->lessons();
    $lesson_count = count($lessons);
    $last = $lessons[$lesson_count - 1];
    $last_lesson_date = Carbon::createFromFormat('Y-m-d H:i:s', $last->created_at);

    if ($meeting_date->gt($last_lesson_date)) {
      return 'past';
    }

    $list = [];
    foreach ($lessons as $key => $lesson) {
      $lesson_date = Carbon::createFromFormat('Y-m-d H:i:s', $lesson->created_at);
      if ($lesson_date->gte($meeting_date)) {
        $list[] = $lesson;
      }
    }

    if (count($list) > 0) {
      return $list[0]->created_at;
    }
  }

  public function allnextlessons()
  {

    $timezone = trim(str_replace(" ", "", $this->time_zone));

    $meeting_date = Carbon::now($timezone);

    $lessons = $this->lessons();

    $future = [];
    foreach ($lessons as $key => $lesson) {
      $lesson_date = Carbon::createFromFormat('Y-m-d H:i:s', $lesson->created_at);
      if ($lesson_date->gte($meeting_date)) {
        $future[] = $lesson;
      }
    }

    return collect($future);
  }

  public function pausedetails()
  {
    if (!$this->paused) {
      return null;
    } else {
      return $this->getPauseDetails;
    }
  }
  public function lessonRecordings()
  {
    return $this->hasMany(LessonRecording::class);
  }
}
