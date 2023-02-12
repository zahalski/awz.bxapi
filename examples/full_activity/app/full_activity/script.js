$(document).ready(function (){

    window.awz_helper = {
        endpointUrl:'',
        arParams: {},
        loader_class: 'awz-main-preload',
        get_preload_html: function (loader_mess) {
            if (!loader_mess) loader_mess = 'загрузка...';
            var ht = '<div class="' + this.loader_class + '">' +
                '<div class="awz-main-load">' +
                '<span>' + loader_mess + '</span>' +
                '</div>' +
                '</div>';
            return ht;
        },
        add_loader: function (el, title) {
            el.append(this.get_preload_html(title));
        },
        remove_loader: function () {
            $('.' + this.loader_class).remove();
        },
        check_ok: function (data) {
            if (typeof (data) === 'object') {
                if (data && data.hasOwnProperty('status') && data.status == 'success') {
                    return true;
                }
            }
            return false;
        },
        ok: {
            get_text: function (mess) {
                return '<div class="ui-alert ui-alert-success">' + mess + '</div>';
            }
        },
        errors: {
            get_text: function(data){
                var mess = [];
                if(typeof(data) === 'object'){
                    if(data && data.hasOwnProperty('status') && data.hasOwnProperty('errors') && data.status == 'error'){
                        var k;
                        for(k in data.errors){
                            var item = data.errors[k];
                            if(typeof(item) == 'object'){
                                if(item.hasOwnProperty('code')){
                                    mess.push(item.code+": "+item.message);
                                }
                            }else if(typeof(item) == 'string'){
                                mess.push(item);
                            }else{
                                mess.push('Ошибка');
                            }
                        }
                    }else{
                        mess.push('Ошибка');
                    }
                }else if(typeof (data) == "string"){
                    mess.push(data);
                }
                return '<div class="ui-alert ui-alert-danger">'+mess.join('; ')+'</div>';
            }
        },
        scrollTop: function(){
            $('html').scrollTop(0);
            BX24.scrollParentWindow(0);
        },
        showMessage: function(msg, el){
            if(!el) el = $('.result-block-messages');
            el.html(msg);
            this.scrollTop();
        },
        loadActivityList: function(signed){
            window.awz_helper.add_loader($('.appWrap'), 'Получение списка активити');
            $('.result-block-messages').html('');
            if(!signed) signed = $('#signed_add').val();
            $.ajax({
                url: this.endpointUrl+'list',
                data: {signed: signed},
                dataType : "json",
                type: "POST",
                success: function (data, textStatus){

                    if(window.awz_helper.check_ok(data)){
                        $('.activity-list').html('');
                        var k = 0;
                        for(k in data['data']['items']){
                            var item = data['data']['items'][k];
                            var ht = '';
                            ht += '<div class="row item-activity-row" data-method="'+item['method']+'" data-code="'+item['code']+'">';
                            ht += '<div class="col-xs-9"><h4>['+item['code']+'] '+item['name']+'</h4><p>'+item['desc']+'</p></div>';
                            ht += '<div class="col-xs-3 text-right bp-buttons"></div>';
                            ht += '</div>';
                            $('.activity-list').append(ht);
                        }
                        window.awz_helper.getActiveBp();
                    }else{
                        var msg = window.awz_helper.errors.get_text(data);
                        window.awz_helper.showMessage(msg);
                    }

                    window.awz_helper.remove_loader();

                },
                error: function (){
                    var msg = window.awz_helper.errors.get_text('внутренняя ошибка сервера');
                    window.awz_helper.showMessage(msg);
                    window.awz_helper.remove_loader();
                }
            });
        },
        getActiveBp: function(){
            $('.item-activity-row').each(function(){
                window.awz_helper.showButtons($(this), $(this).attr('data-code'), 'add');
            });
            var calledCounter = 0;
            BX24.callMethod(
                'bizproc.robot.list',
                {},
                function(res)
                {
                    var codes = [];
                    try {
                        var k;
                        for (k in res.answer.result) {
                            var code = res.answer.result[k];
                            codes.push(code);
                        }
                    } catch (e) {
                    }
                    window.awz_helper.setCodesActive(codes, 'robot');
                    calledCounter += 1;
                    if(calledCounter>1){
                        $('.bp-load-hide-btn').show();
                    }
                }
            );
            BX24.callMethod(
                'bizproc.activity.list',
                {},
                function(res)
                {
                    var codes = [];
                    try {
                        var k;
                        for (k in res.answer.result) {
                            var code = res.answer.result[k];
                            codes.push(code);
                        }
                    } catch (e) {
                    }
                    window.awz_helper.setCodesActive(codes, 'bp');
                    calledCounter += 1;
                    if(calledCounter>1){
                        $('.bp-load-hide-btn').show();
                    }
                }
            );
        },
        setCodesActive: function(codes, type){
            console.log(codes);
            $('.item-activity-row').each(function(){
                var code = $(this).attr('data-code');
                if(codes.indexOf(code)>-1){
                    window.awz_helper.showButtons($(this), code, 'del');
                }
            });
        },
        showButtons: function(el, code, type){
            if(type === 'add'){
                el.find('.bp-load').remove();
                el.find('.bp-buttons').append('<div class="bp-load bp-load-hide-btn"><a class="add ui-btn ui-btn-primary ui-btn-icon-done" href="#">установить</a></div>');
            }
            if(type === 'del'){
                el.find('.bp-load').remove();
                el.find('.bp-buttons').append('<div class="bp-load"><a class="remove ui-btn ui-btn-danger-light ui-btn-icon-alert" href="#">удалить</a></div>');
            }
        }
    }


    $(document).on('click', '.bp-buttons .add', function(e){
        e.preventDefault();
        window.awz_helper.add_loader($('.appWrap'), 'Получение списка активити');
        $('.result-block-messages').html('');
        var signed = $('#signed_add').val();
        var bp_id = $(this).parents('.item-activity-row').attr('data-method');
        var bp_code = $(this).parents('.item-activity-row').attr('data-code');
        var type = (bp_code.indexOf('_r') == (bp_code.length-2)) ? 'robot' : 'bp';

        $.ajax({
            url: window.awz_helper.endpointUrl+'getActivity',
            data: {signed: signed, code: bp_id, type: type},
            dataType : "json",
            type: "POST",
            success: function (data, textStatus){

                if(window.awz_helper.check_ok(data)){
                    var method = 'bizproc.activity.add';
                    if(type == 'robot'){
                        method = 'bizproc.robot.add';
                    }
                    BX24.callMethod(
                        method,
                        data['data']['activity'],
                        function(result)
                        {
                            if(result.error())
                                alert('Error: ' + result.error());
                            else
                                window.awz_helper.getActiveBp();
                        }
                    );
                }else{
                    var msg = window.awz_helper.errors.get_text(data);
                    window.awz_helper.showMessage(msg);
                }

                window.awz_helper.remove_loader();

            },
            error: function (){
                var msg = window.awz_helper.errors.get_text('внутренняя ошибка сервера');
                window.awz_helper.showMessage(msg);
                window.awz_helper.remove_loader();
            }
        });

    });
    $(document).on('click', '.bp-buttons .remove', function(e){
        e.preventDefault();
        var bp_id = $(this).parents('.item-activity-row').attr('data-code');
        var method = 'bizproc.activity.delete';
        if(bp_id.indexOf('_r') == (bp_id.length-2)){
            method = 'bizproc.robot.delete';
        }
        BX24.callMethod(
            method,
            {code: bp_id},
            function(result)
            {
                if(result.error())
                    alert('Error: ' + result.error());
                else
                    window.awz_helper.getActiveBp();
            }
        );
    });
    $(document).on('click', '.ui-block-title-actions-show-hide', function(e){
        e.preventDefault();
        var parent = $(this).parents('.ui-block-wrapper');
        if(parent.find('.ui-block-content').hasClass('active')){
            parent.find('.ui-block-content').removeClass('active');
            $(this).html('Развернуть');
        }else{
            parent.find('.ui-block-content').addClass('active');
            $(this).html('Свернуть');
        }
    });

});