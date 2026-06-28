<?php

use App\Events\PluginWasEnabled;
// use App\Events\PluginWasDeleted;  // 暂时注释，调试用
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return [
    PluginWasEnabled::class => function ($plugin) {
        if (!Schema::hasTable('oidc_user_bindings')) {
            Schema::create('oidc_user_bindings', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('uid')->comment('BlessingSkin user ID');
                $table->string('oidc_sub', 255)->comment('OIDC subject claim');
                $table->string('oidc_issuer', 512)->nullable()->comment('OIDC issuer URL for multi-provider support');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();

                $table->unique(['oidc_sub', 'oidc_issuer'], 'idx_oidc_unique');
                $table->index('uid', 'idx_uid');
            });
        }
    },

    // PluginWasDeleted::class => function ($plugin) {
    //     // 调试期间暂时注释，防止误删映射数据
    //     Schema::dropIfExists('oidc_user_bindings');
    // },
];
