M.block_buyways={};

(function(){
    /**
     * Private vars
     */
    var myY=0,
    hierSelid=0,
    check={
        msg:'',
        stClass:'error',
        ret:false
    },
    trans_handler={
        start:function(id,args){
            check.msg=args.start;
            check.ret=false;
            check.stClass='check';
            this.msg_user(check);
        },
        success: function(id,resp,args){
            var o=myY.JSON.parse(resp.responseText);
            check.msg=o.message;
            if(o.ret=='true'){
                check.stClass='success';
            }else{
                check.stClass='error';
            }
            check.ret=o.ret;
        },
        failure: function(id,resp,args){
            check.msg=args.failure;
            check.ret=false;
            check.stClass='fail';
        },
        msg_user:function(msg){
            var el=myY.one('#id_profile_field_hierarchytext'),elspan;
            if(el){
                elspan=el.get('parentNode').one('span');
                
                if(!elspan){
                    elspan=el.get('parentNode').insertBefore('<span/>',el);
                    elspan.set('id','id_error_profile_field_hierarchytext');
                }
                
                if(elspan){
                    elspan.setAttribute('class',msg.stClass);
                    if(msg.ret===false && typeof _qfMsg !== 'undefined'){
                        _qfMsg += '\n' + msg.msg;
                    }else{
                        if(msg.msg===''){
                            elspan.set('innerHTML',msg.msg);
                        }else{
                            elspan.set('innerHTML',msg.msg + '<br>');
                        }
                    }
                }
            }
        }
    },
    trans_args={
        start:M.util.get_string('buyways_checking', 'block_lp_hierarchy'),
        success: M.util.get_string('buyways_checking_error', 'block_lp_hierarchy'),
        failure: M.util.get_string('buyways_checking_fail', 'block_lp_hierarchy')
    },
    last_check={
        selval:-1,
        txtval:-1,
        lstres:false
    };
    
    M.block_buyways.init = function(Y){
            myY=Y;
    };
    
    M.block_buyways.hnd_selchng=function(e){
        var t=myY.one('#id_profile_field_hierarchytext');
        if(t){
            M.block_buyways.validate(t.get('value'));
        }
    }

    M.block_buyways.validate=function(val){
        
        var hSel=false,cfg,request;
        
        if(val.length !== 4){
            return false;
        }
                
        if(hierSelid === 0){
            hSel=myY.all('#fitem_id_profile_field_hierarchyid select');
            if(hSel.size()>1){
                hSel=hSel.item(1);
            }else{
                hSel=hSel.item(hSel.size()-1);
            }
            if(hSel){ //check for truthy
                if(hSel.get('id')==""){
                   hSel.setAttribute('id','sel_'+Math.round(Math.random()*100));
                }
                hierSelid=hSel.get('id');
            }
            hSel.on('change', M.block_buyways.hnd_selchng);
        }else{
            hSel=myY.one('#'+hierSelid);
        }
        
        if(hSel){
            
            if(last_check.selval===hSel.get('value') && last_check.txtval === val){
                return last_check.lstres;
            }
            
            if(hSel.get('value') > 0){
                last_check.selval=hSel.get('value');
                last_check.txtval = val;
                
                 cfg={
                    method: 'GET',
                    data:{
                        hier: (hSel.get('value')),
                        sid: val
                    },
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    on: {
                        start: trans_handler.start,
                        success: trans_handler.success,
                        failure: trans_handler.failure
                    },
                    context: trans_handler,
                    arguments: trans_args,
                    sync:true //hold execution so we can return true/false
                };
                
                request=myY.io('../blocks/lp_hierarchy/buyways_checker.php',cfg);
                
                trans_handler.msg_user(check);
                
                last_check.lstres=check.ret;
                
                return check.ret;
                
            }else{
                //nothing was selected for the level above
                return false;
            }
            
        }
        
        return true;
        
    };
}());

