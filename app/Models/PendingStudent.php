<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PendingStudent extends Model {
    protected $fillable = ['name','class','roll_no','student_status','teacher_id','approval_status'];
    
    public function teacher() {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}