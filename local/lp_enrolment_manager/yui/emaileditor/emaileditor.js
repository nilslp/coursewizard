YUI.add('moodle-local_lp_enrolment_manager-emaileditor', function(Y){    
    var YAHOO = Y.YUI2;
    var CUSTOM_EMAIL    = 'custom',
        PREVIEW_EMAIL   = 'Preview Email',
        RESTORE_EMAIL   = 'Restore Email'
        EMAIL_STATUS    = 0;
    
    var PANEL_WIDTH     = '480px',
        BODY_COLS       = 68,
        BODY_ROWS       = 10;
         
    var EMAILEDITOR = function(config) {
        EMAILEDITOR.superclass.constructor.apply(this, arguments);
    };
    
    EMAILEDITOR.NAME = 'emaileditor';
    EMAILEDITOR.ATTRS = {
        target : 'emaileditor',
        container : 'emailcontainer'
    };
    
    Y.extend(EMAILEDITOR, Y.Base, {
        initializer : function (cfg) {
            this.div = Y.one('#'+cfg.target);
            if (this.div) { 
                this.div.delegate('click', this._buttonClicked, 'input', this);
                this.div.delegate('click', this._valueChanged, 'select');
                this.body = Y.Node.create( '<textarea cols="'+BODY_COLS+'" rows="'+BODY_ROWS+'" id="email_body"></textarea>' ); 
            }
            this.panel = new YAHOO.widget.Panel(cfg.container, {
                width:PANEL_WIDTH,
                visible : false, 
                draggable : true, 
                constraintoviewport : true,
                fixedcenter: 'contained',
                iframe: true,
                zIndex : 1001
            }); 
            this.panel && this.panel.render();
            this.panel && this.panel.subscribe("hide", this.hide, this, true);
            this.bodytext = cfg.body;
        },
        destructor : function () {      
            this.div && this.div.detachAll();      
        },
        courseid : -1,
        div : {},
        panel : {},
        body : {},
        controls : {},
        closeBtn : false,
        bodytext : '',
        replacements : {},
        course : {
            admins : ['no available email'],
            title : 'Unknown'
        },
        fields : {
            sender : {
                group : 'select_sender_group',
                id : 'email_sender',
                tag : 'select',
                label : 'Sender (Select from dropdown)'
            },
            custom_sender : {
                group : 'custom_sender_group',
                id : 'custom_sender',
                tag : 'input',
                inline : true,
                label : 'Enter custom email',
                atts : {
                    type : 'text'
                }                
            },
            subject : {
                group : 'email_subject_group',
                id : 'email_subject',
                tag : 'input',
                inline : true,
                label : 'Subject',
                atts : {
                    type : 'text',
                    size : 40
                }
            },
            showEmail : {
                group : 'email_preview_group',
                id : 'email_preview',
                tag : 'input',
                inline : true,
                atts : {
                    type : 'button',
                    value : PREVIEW_EMAIL                    
                }
            }
        },
        getData : function() {
            var ret = {
                body:this.bodytext,
                preview:this._replaceTags(this.bodytext, this.replacements),
                sender:'',
                subject:''
            };
            var sender = Y.one('#email_sender');
            var subject = Y.one('#email_subject');
            
            if (sender) {
                if (sender.get('value') === CUSTOM_EMAIL) {
                    sender = Y.one('#custom_sender');
                    ret.sender = (sender && sender.get('value'));
                } else {
                    ret.sender = sender.get('value');
                }
            }
            
            ret.subject = subject && subject.get('value');
            if (!ret.subject) {
                ret.subject = '';
            }
            
            return ret;
        },
        _updateReplacements : function( replacements ){            
            var sender = Y.one('#email_sender');
            
            if (sender) {
                if (sender.get('value') === CUSTOM_EMAIL) {
                    sender = Y.one('#custom_sender');
                    replacements['My email'].val = (sender && sender.get('value'));
                } else {
                    replacements['My email'].val = sender.get('value');
                }
            }
            return replacements;
        },
        show : function () {          
            this.panel.show();
        },
        hide : function (e) {
            if (0 != EMAIL_STATUS) {
                var button = Y.one('#'+this.fields.showEmail.id);
                button && button.set('value', PREVIEW_EMAIL);
                this._swapText(RESTORE_EMAIL);
            }
            this.bodytext = this.body.get('value'); 
        },
        form : function ( props ) {
            var k = '';
            var field = {};
            var fieldsets = {};
            var controls = [];
            if (!this.div || !this.panel) {
                return;
            }
            
            if (props && this.courseid !== props.course.id) { // only reset all if course has changed
                props.adminemails.length || props.adminemails.unshift( '' );
                props.adminemails.push( {name:'-- use custom email --',value:'custom'} );
                this.fields.sender.content = this._buildOptions(props.adminemails);
                this.fields.subject.atts.value = props.subject;
                this.body.set('disabled',false);
                if (!this.bodytext) {
                    this.bodytext = props.body;                    
                }
                this.body.set('value', this.bodytext);
                
                
                for (k in this.fields) {
                    if (this.fields.hasOwnProperty(k)) {
                        if (!fieldsets[this.fields[k].group]) {
                             fieldsets[this.fields[k].group] = [];
                        } 
                        fieldsets[this.fields[k].group].push(this._buildField(this.fields[k]));
                    }
                }
                
                this.replacements = props.replacements;
                for (k in this.replacements) {
                    if (this.replacements.hasOwnProperty(k)){
                        field = {};
                        field.tag = 'input';
                        field.id = this.replacements[k].tag;
                        field.inline = true;
                        field.atts = { type:'button', value:k, 'class':'insert'};
                        controls.push(this._buildField(field));                    
                    }
                }
                
                this.div.setContent('');
                this.div.append( Y.Node.create( '<fieldset>'+this._joinFields(fieldsets)+'</fieldset><fieldset id="editcontrols">'+controls.join('')+'</fieldset>' ) );
                this.div.append( this.body );
                
                if (!this.closeBtn) {
                    var closeContainer = Y.Node.create('<div style="text-align:right;"></div>'); 
                    this.closeBtn = Y.Node.create('<input type="button" value="Close" />');
                    this.closeBtn.on('click',function (e){
                        this.panel.hide();
                    },
                    this);
                    closeContainer.append(this.closeBtn);
                    this.div.append( closeContainer );
                }
                
                Y.one('#custom_sender_group').setStyle('display', 'none');
                this.courseid = props.course.id;
            }
        },
        _joinFields : function ( fieldsets ) {
            var k = '';
            var ret = [];
            for (k in fieldsets){
                ret.push('<fieldset id="'+k+'">');
                if (fieldsets.hasOwnProperty(k)) {
                    fieldsets[k].length && ret.push(fieldsets[k].join(''));
                }
                ret.push('</fieldset>');
            }
            
            return ret.join('');
        },
        _swapText : function( action ){
            var rep = this.replacements; //this._updateReplacements(this.replacements);
            if (action.indexOf(PREVIEW_EMAIL) !== -1) { // on preview, save the body text
                this.bodytext = this.body.get('value'); 
            }
            
            if (this.bodytext.indexOf(this.body.get('value')) !== -1) {
                this.body.set('value', this._replaceTags(this.bodytext, rep));
                EMAIL_STATUS = 1;
                this.div.all('input.insert, #email_body').each(function(node){
                    node.set('disabled',true);
                });
                return true;
            } 
            // else restore template text
            EMAIL_STATUS = 0;
            this.body.set('value', this.bodytext);
            this.div.all('input.insert, #email_body').each(function(node){
                node.set('disabled', false);
            });
            return false;
        },
        _replaceTags : function(src, replacements){
            var rep = {};
            var k = '';
                        
            for (k in replacements){
                if (replacements.hasOwnProperty(k)) {
                    rep[replacements[k].tag] = replacements[k].val;  
                }
            }
            return Y.Lang.sub(src, rep);
        },
        _buttonClicked : function (e) {
            if (e.target.hasClass('insert')){
                var str = '{' + this.replacements[e.target.get('value')].tag + '}';     // add the curly braces for later manipulation by Y.Lang.sub
                if (str && this.body) {
                    this._insertAtCursor(this.body, str);
                }
                this.bodytext = this.body.get('value');
            } else if (e.target.get('id').indexOf('email_preview') !== -1) {
                if (this._swapText(e.target.get('value'))){
                    e.target.set('value', RESTORE_EMAIL);
                } else {                    
                    e.target.set('value', PREVIEW_EMAIL);
                }
            }        
        },
        _insertAtCursor : function (field, value) {
            var sel = {};
            var fval = '';
            if (document.selection){ // ie
                field.focus();
                sel = document.selection.createRange();
                sel.text = value;
            } else if (field.get('selectionStart')) { // moz
                fval = field.get('value');
                fval = fval.substr(0,field.get('selectionStart')) +
                    value +
                    fval.substr(field.get('selectionEnd'));
                field.set('value', fval);
            } else {
                field.set('value', field.get('value') + value);
            }
        },
        _valueChanged : function (e){
            var customsender = Y.one('#custom_sender_group');
            if (this.get('value') === CUSTOM_EMAIL){
                customsender.setStyle('display', 'block');
            } else {
                customsender.setStyle('display', 'none');
            }
        },
        _buildField : function (field) {            
            var k = '';
            var j = '';
            var f = {};
            var label = ''; 
            var atts = field.id ? ['name="'+field.id+'"','id="'+field.id+'"'] : [];
            
            for (k in field.atts) {
                if (field.atts.hasOwnProperty(k)){
                    atts.push(k+'="'+field.atts[k]+'"');
                }
            }                      
            
            if (field.label){
                label = '<label for="'+field.id+'">'+field.label+"</label>";
            }
            return label + '<'+field.tag+' '+atts.join(' ')+ ( field.inline ? ' />' : '>'+this._fieldContent(field.tag, field.content)+'</'+field.tag+'>');
        },
        _buildOptions : function ( list ) {
            var options = [];
            var val = '';
            var name = '';
            var i = 0;
            for (i = 0; i < list.length; ++i) {
                val = list[i].value || list[i];
                name = list[i].name || list[i];
                options.push({tag:'option', content:name, atts:{value:val}});
            }
            return options;
        },
        _fieldContent : function ( ftype, content ) {
            var k = '';
            var i = 0;
            var out = [];
            switch (ftype) {
                case 'select': {
                    for (i = 0; i < content.length; ++i) {
                        out.push(this._buildField(content[i]));
                    }                    
                    return out.join('');
                }                
                default:
                    return content;
            }
            return '';
        }
    });
    
    M.local_lp_enrolment_manager = M.local_lp_enrolment_manager || {};
    M.local_lp_enrolment_manager.init_emaileditor = function(config) {
        M.EMAILEDITOR = new EMAILEDITOR(config);
        return M.EMAILEDITOR;
    }
    
}, '@VERSION@', {requires:['base', 'node', 'event-delegate', 'yui2-container', 'yui2-dragdrop']});