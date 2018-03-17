<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTopicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->comment('标题');
            $table->text('body')->comment('内容');
            $table->integer('user_id')->comment('用户id');
            $table->integer('category_id')->comment('分类id');
            $table->unsignedInteger('reply_count')->comment('评论数')->default(0);
            $table->unsignedInteger('view_count')->comment('点击数')->defalut(0);
            $table->integer('last_reply_user_id')->comment('最后回复用户')->nullable();
            $table->unsignedSmallInteger('order')->comment('排序')->default(0);
            $table->string('excerpt')->comment('话题摘录')->nullable();
            $table->string('slug')->comment('title翻译')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('topics');
    }
}
