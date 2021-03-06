<html>
<head>
  <title>json ams1 example</title>
  <meta charset="utf-8">
  <script type="text/javascript" src="//d3js.org/d3.v3.js"></script>
  
  <!--<script type="text/javascript" src="http://cpettitt.github.io/project/dagre-d3/latest/dagre-d3.js"></script>-->
  <!--<script src="http://www.samsarin.com/project/dagre-d3/latest/dagre-d3.js" charset="utf-8"></script>-->
  <script src="dagre-d3.js" charset="utf-8"></script>
  <script src="http://code.jquery.com/jquery-2.2.4.js"></script>
  <link rel="stylesheet" type="text/css" href="styles.css" />
</head>
<body>


<svg width="1500" height="1500">
    <g></g>
</svg>

<script>

window.onload = function(){
//$(function(){
  
  //$.getJSON('./screener-AMS1-prod-Insomnia.json', function( sceenerLogicJson ) {
  //$.getJSON('./screener-AMS1-prod-Intro.json', function( sceenerLogicJson ) {
  //https://sb.acurian.com/sb/study/842/logicbuilder/getScreenerLogic?selModuleName=Intro&isShowAllMod=false&isInitLB=true
  //$.getJSON('./screener-AMS1-prod-RA_2821.json', function( sceenerLogicJson ) {
  //$.getJSON('./screener-3889OUS-prod-Intro.json', function( sceenerLogicJson ) {
  $.getJSON('./screener-3889OUS-prod-Core.json', function( sceenerLogicJson ) {


    let recursed_nodes_obj = {};
    let recursed_nodes_arr = [];
    let regex_reg_question = /([a-zA-Z0-9_\- ]+)-QS([0-9]+)$/;
    let regex_ends_with_sub_question   = /([a-zA-Z0-9_\- ]+)-QS(([0-9]+)\.([0-9]+))$/;
    let regex_ends_with_sub_question_2 = /([a-zA-Z0-9_\- ]+)-QS(([0-9]+)_([0-9]+))$/;
    function convertQuestionUnderscoreKeyToDotFormat(answerJsonQuestion) {
      let matches = regex_ends_with_sub_question_2.exec(answerJsonQuestion);
      if(matches !==null) {
       let module_name = matches[1];
       let main_question_number = matches[3];
       let sub_question_number  = matches[4];
       let combo_name = module_name+'-QS'+main_question_number+'.'+sub_question_number;
       return combo_name;
      }
    }
    
    //QS1 to QS2,   or QS1
    function convertModuleQuestionToQuestion(full_module_from_question_cd,full_module_to_question_cd) {
      
      if(full_module_to_question_cd !== undefined) {
        let matches = regex_reg_question.exec(full_module_to_question_cd);
        if(matches!==null) {
           let module_name = matches[1];
           let main_question_number = matches[2];
           
            let matches2 = regex_reg_question.exec(full_module_from_question_cd);
            if(matches2!==null) {
              let module_name2 = matches2[1];
              //let main_question_number2 = matches2[2];
              
              if(module_name2===module_name) {
                return 'QS'+ main_question_number;      
              } else {
                return module_name +'-QS'+ main_question_number;
              }
              
            } else {         
              return 'QS'+ main_question_number;      
            }
        }
      } else {
        let matches = regex_reg_question.exec(full_module_from_question_cd);
        if(matches!==null) {
          let module_name = matches[1];
          let main_question_number = matches[2];
          return 'QS'+ main_question_number;               
        } else {         
          return 'QS##';      
        }
      }
    }
    function evalQuestionForLogic(question) {
      let proto_logic_obj = {proto_logic_type: 'none', proto_main_logic: [], sub_question_logic: [] };
      
      let has_main_question_proto_logic = false;
      if(question.main_question_logic && typeof question.main_question_logic.logic[1] !== 'undefined' && question.main_question_logic.logic[1].rules.length > 0 ) {
        //note: outside modules don't have their logic here so you won't get a main_question_logic for them
        let logic = question.main_question_logic.logic[1];//protocol logic
        let logic_array = logic.rules || []; //ask Dmitriy would there ever be > 1 logic section in the array
        //let whole_entire_logic_for_this_question = from_question_logic_value.logic[0].logicSummaryText;
        has_main_question_proto_logic = true;
        for( let i = 0; i < logic_array.length; i++ ){          
          let rule = logic_array[i];
          //let order_id = rule.orderID;
          if( rule.selectedQualifier == "Disqualify") {
            if(proto_logic_obj.proto_logic_type=='none') {
              proto_logic_obj.proto_logic_type = 'disqualify';
            } else if (proto_logic_obj.proto_logic_type == 'qualify') {
              proto_logic_obj.proto_logic_type = 'both';
            }
          } else if ( rule.selectedQualifier == "Qualify") {
            if(proto_logic_obj.proto_logic_type=='none') {
              proto_logic_obj.proto_logic_type = 'qualify';
            } else if (proto_logic_obj.proto_logic_type == 'disqualify') {
              proto_logic_obj.proto_logic_type = 'both';
            }
          }
           //"selectedQualifier": "Qualify"|"Disqualify"
           //"selectedProtocol" array of proto strings
           //"selectedDQLabel": "No swollen/tender joints",     
           proto_logic_obj.proto_main_logic.push( {rule_text: rule.text, type: rule.selectedQualifier, protocols: rule.selectedProtocol, label: rule.selectedDQLabel} );
        }
      }
      
      //and question.subquestions...logic
      //check subquestion logic now
      if( question.subquestions.length > 0 )  {
        let has_sub_question_proto_logic = false;
        for( let i = 0; i < question.subquestions.length; i++ ){          
          let subquestion = question.subquestions[i];
          let sub_logic = subquestion.logic.logic[0];//protocol logic
          let sub_logic_array = sub_logic.rules || []; //ask Dmitriy would there ever be > 1 logic section in the array
          //let whole_entire_logic_for_this_question = from_question_logic_value.logic[0].logicSummaryText;
          for( let i = 0; i < sub_logic_array.length; i++ ){
            has_sub_question_proto_logic = true;
            
            let sub_rule = sub_logic_array[i];
            //let order_id = rule.orderID;
            if( sub_rule.selectedQualifier == "Disqualify") {
              if(proto_logic_obj.proto_logic_type=='none') {
                proto_logic_obj.proto_logic_type = 'disqualify';
              } else if (proto_logic_obj.proto_logic_type == 'qualify') {
                proto_logic_obj.proto_logic_type = 'both';
              }
            } else if ( sub_rule.selectedQualifier == "Qualify") {
              if(proto_logic_obj.proto_logic_type=='none') {
                proto_logic_obj.proto_logic_type = 'qualify';
              } else if (proto_logic_obj.proto_logic_type == 'disqualify') {
                proto_logic_obj.proto_logic_type = 'both';
              }
            }
             //"selectedQualifier": "Qualify"|"Disqualify"
             //"selectedProtocol" array of proto strings
             //"selectedDQLabel": "No swollen/tender joints",     
             proto_logic_obj.sub_question_logic.push( {rule_text: sub_rule.text, type: sub_rule.selectedQualifier, protocols: sub_rule.selectedProtocol, label: sub_rule.selectedDQLabel} );
          }
        }
      }
      return proto_logic_obj;
    } 
    function recurseLogicTree(module_name, full_sb_question_cd, first_question_module){
        
          //add this node if not already processed (avoid duplicate processing)
          if(recursed_nodes_obj.hasOwnProperty(full_sb_question_cd)) {
            return;
          }
          let extra_question_info = questionsJson.find((elem) => { 
            if(full_sb_question_cd==elem.caption){ return elem; } 
          });
          extra_question_info.subquestions = [];
          //is this related to this module, or a Close, or an 'outside' module.
          if(module_name===first_question_module) {
            extra_question_info['nodeModuleType'] = 'same_module';
            recursed_nodes_obj[full_sb_question_cd] = extra_question_info;
            recursed_nodes_arr.push(extra_question_info);//in case realize I need an array later
          } else if(module_name==='Close') {
            extra_question_info['nodeModuleType'] = 'close_module';
            recursed_nodes_obj[full_sb_question_cd] = extra_question_info;
            recursed_nodes_arr.push(extra_question_info);//in case realize I need an array later
          } else {
            extra_question_info['nodeModuleType'] = 'outside_module';        
            recursed_nodes_obj[full_sb_question_cd] = extra_question_info;
            recursed_nodes_arr.push(extra_question_info);//in case realize I need an array later
            return; //stop recursing into another module as it can build some repetitive long trees and maybe loops
          }

          //add protocol/logic information from the main question to this array
          //TODO
          
          //add subquestion information to this node? for protocol info later
          //TODO or after this loop (for now trying after this loop as might be less confusing)
          

          
          //search its logic children and recurse more nodes
          let has_question_logic = logicJson.hasOwnProperty(full_sb_question_cd);
          if( has_question_logic ) {
                
              let from_question_logic_key = full_sb_question_cd;
              let from_question_logic_value = logicJson[full_sb_question_cd];
              
              recursed_nodes_obj[full_sb_question_cd].main_question_logic = from_question_logic_value;
              let found = recursed_nodes_arr.find((elem)=> { 
                if(elem.caption===from_question_logic_key){ return elem;} 
              });
              found.main_question_logic = from_question_logic_value; 
            
              //1st add logic if exists
            
              let is_child_question = from_question_logic_value.isChildQuestion;           
              if(!is_child_question) {
                //let question_id = from_question_logic_value.projectQuestionId;
                let logic = from_question_logic_value.logic[0];
                let logic_array = logic.rules || []; //ask Dmitriy would there ever be > 1 logic section in the array
                //let whole_entire_logic_for_this_question = from_question_logic_value.logic[0].logicSummaryText;
                for( let i = 0; i < logic_array.length; i++ ){
                  let rule = logic_array[i];
                  //let order_id = rule.orderID;
                  let goto_question_id = rule.rule_action.projQsId;
                  let goto_question = questionsJson.find((elem) => { 
                    if(goto_question_id==elem.projQsId){ return elem; } 
                  });
                  let goto_question_cd = goto_question.caption;
                  //let logic_broken = rule.rule_action.logicBroken;
                  //let logic_text = rule.text;              
                  console.log(full_sb_question_cd +' to '+ goto_question_cd +'(aka '+goto_question_id+')');
                  
                  let matches = regex_reg_question.exec(goto_question_cd);
                  if(matches!==null) {
                     let module_name = matches[1];
                     let main_question_number = matches[2];
                     let combo_name = module_name+'-QS'+main_question_number;
                     
                     recurseLogicTree(module_name,goto_question_cd, first_question_module);              
                  }
                }//for
              }//if is_child_question
                    
          }//if has logic so points to other questions we need to add (so we come up with most minimal tree)
    }    

    let questionsJson = sceenerLogicJson.projQuestions || {};
    let logicJson = sceenerLogicJson.scrLogicMap || {};
    //some extra information in questions might be useful to display... but look at later!
    //let questions_lookup = questionsJson.map((elem)=> {questionID: elem.projQsId, caption: elem.caption} );

    //let first_question_module = 'Intro'; //TODO: get from S.B.
    //let first_question_cd = 'Intro-QS1'; //TODO: get from S.B.
    let first_question_module = 'Core'; //TODO: get from S.B.
    let first_question_cd = 'Core-QS1'; //TODO: get from S.B.
    //let first_question_module = 'Insomnia'; //TODO: get from S.B.
    //let first_question_cd = 'Insomnia-QS1'; //TODO: get from S.B.
    
    //alternate: follow the tree from 1st question...
    recurseLogicTree(first_question_module, first_question_cd, first_question_module);
    
    //add subquestion information to the main question
    Object.entries(logicJson).forEach( ([from_question_key, from_question_logic_value]) => {
          //if its subquestion, skip for now (may want to check protocol logic on subquestions later if those exist there; I'm not sure if they do).
          if(from_question_logic_value.isChildQuestion) {
            let parent_sb_question_id = from_question_logic_value.parentProjQsId;
            let child_sb_question_id = from_question_logic_value.projectQuestionId;
            let child_sb_question_cd = convertQuestionUnderscoreKeyToDotFormat(from_question_key);
            
            let child_question_from_question = questionsJson.find((elem) => { 
              if(child_sb_question_id==elem.projQsId){ return elem; } 
            });
            
            //find that parent/main question's code and add this subquestion to that main question's subquestions array
            let subquestion_parent = questionsJson.find((elem)=>{
              if(elem.projQsId==parent_sb_question_id){ return elem; }
            });
            let subquestion_obj = { question: child_question_from_question, logic: from_question_logic_value};
            //modify the object
            
            //some subquestions may not have a parent which is being pointed at, so dont add if it doesn't exist in the flow
            if( recursed_nodes_obj.hasOwnProperty(subquestion_parent.caption)) {
              recursed_nodes_obj[subquestion_parent.caption].subquestions.push( subquestion_obj );
              //modify the array
              /* think this points the same object so not needed since passed-by-reference
              let found = recursed_nodes_arr.find((elem)=> { 
                if(elem.caption===subquestion_parent.caption){ return elem;} 
              });
              found.subquestions.push ( subquestion_obj );
              */
            } 
          }          
    });

    
    //console.log(recursed_nodes_arr);
    console.dir(recursed_nodes_obj);
    //console.log(recursed_nodes_arr);
    console.dir(recursed_nodes_arr);
    console.log('done');


    var g = new dagreD3.graphlib.Graph({ compound: false, multigraph: true }).setGraph({});
    /*
    By default this will create a directed graph that does not allow multi-edges or compound nodes. The following options can be used when constructing a new graph:

    *directed: set to true to get a directed graph and false to get an undirected graph. An undirected graph does not treat the order of nodes in an edge as significant. In other words, g.edge("a", "b") === g.edge("b", "a") for an undirected graph. Default: true.
    *multigraph: set to true to allow a graph to have multiple edges between the same pair of nodes. Default: false.
    *compound: set to true to allow a graph to have compound nodes - nodes which can be the parent of other nodes. Default: false.
    To set the options, pass in an options object to the Graph constructor. For example, to create a directed compound multigraph:

    var g = new Graph({ directed: true, compound: true, multigraph: true });
    */
    let nodes = [];

    console.log('be4 loop node creation');
    //var regex_ends_with_sub_question = new RegExp("\\.([0-9]+)$");
    
    for(let i = 0; i < recursed_nodes_arr.length; i++){
      //let question = questionsJson[i];
      let question = recursed_nodes_arr[i];
      if (question.projQsId !== null) {
        if( !regex_ends_with_sub_question.test(question.caption) ) {
          if(question.questionCd !== null) {//maybe dont add if questionCd is null, 
            //console.log('adding node: '+ question.caption);
            console.log(question);
            let proto_logic_obj = evalQuestionForLogic(question);
            
            let protocol_html = '';
            if(proto_logic_obj.proto_logic_type!='none') {
              //console.log('Lets do some work!');              
              if (proto_logic_obj.proto_main_logic.length > 0) {
                console.log('Main Proto Logic!');
                protocol_html += '<div class="main_question_proto_logic">';
                protocol_html += '<b>Protocol logic (on main question):</b><br/>';
                for(let i=0; i < proto_logic_obj.proto_main_logic.length; i++) {
                  protocol_html += '<div class="rule">';
                    let rule = proto_logic_obj.proto_main_logic[i];
                    protocol_html += `<b>Type: </b>: ${rule.type}<br/>`;
                    protocol_html += `<b>Label: </b>: ${rule.label}<br/>`;
                    protocol_html += `<b>Protocols: </b>: ${rule.protocols} <br/>`;
                    protocol_html += `<b>Text: </b>: ${rule.rule_text}`;
                  protocol_html += '</div>';
                }
                protocol_html += '</div>';
              }
            }

            if (proto_logic_obj.sub_question_logic.length > 0) {
              //console.log('Sub Proto Logic!');  
              if (proto_logic_obj.sub_question_logic.length > 0) {
                console.log('Main Proto Logic!');
                protocol_html += '<div class="sub_question_proto_logic">';
                protocol_html += '<b>Protocol logic (on sub question):</b><br/>';
                for(let i=0; i < proto_logic_obj.sub_question_logic.length; i++) {
                  protocol_html += '<div class="rule">';
                    let rule = proto_logic_obj.sub_question_logic[i];
                    protocol_html += `<b>Type: </b>: ${rule.type}<br/>`;
                    protocol_html += `<b>Label: </b>: ${rule.label}<br/>`;
                    protocol_html += `<b>Protocols: </b>: ${rule.protocols} <br/>`;
                    protocol_html += `<b>Text: </b>: ${rule.rule_text}`;
                  protocol_html += '</div>';
                }
                protocol_html += '</div>';
              }
            }
            
            nodes.push( {'qs_code': question.caption, 'qs_id': question.projQsId, 'hovertext': '<div>'+'<b>QuestionType: </b>'+ question.answerType +"<br/><b>Question Info: </b>"+ question.questionText + ((question.alias !== null) ? ("<br/><b>Alias: </b>"+ question.alias) : '')+ protocol_html +'</div>', 'proto_logic_type': proto_logic_obj.proto_logic_type } );
          }//answerType questionText alias if not null
        }
      }
    }
    //console.log(nodes);
    //console.log('after loop node creation');

    //console.log('be4 setNode');
    // Automatically label each of the nodes
    nodes.forEach(function(node) {
        g.setNode(node.qs_code, { label: node.qs_code, shape: "circle", class: [node.proto_logic_type], hovertext: node.hovertext  });  //style: 'fill: red' 
    });
    //console.log('after setNode');

    function escapeAnswerLogic(input) {      
      return ('' + input) /* Forces the conversion to string. */
        .replace(/&/g, '&amp;') /* This MUST be the 1st replacement. */
        .replace(/'/g, '&apos;') /* The 4 other predefined entities, required. */
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        /*
        You may add other replacements here for HTML only 
        (but it's not necessary).
        Or for XML, only if the named entities are defined in its DTD.
        */ 
    }
    
    
    Object.entries(logicJson).forEach( ([from_question_key, from_question_logic_value]) => {
          //if its subquestion, skip for now (may want to check protocol logic on subquestions later if those exist there; I'm not sure if they do).
          //also check if the node exists, we may have not cared about this questions logic if it wasn't included based on the above rules.
          if(from_question_logic_value.isChildQuestion || (!recursed_nodes_obj.hasOwnProperty(from_question_key)) ) {
            return;
          }
          console.log('checking entry: '+from_question_key);
              
          //skip over this node if its a child question since those don't have logic
          let is_child_question = from_question_logic_value.isChildQuestion; 
          let question_id = from_question_logic_value.projectQuestionId;
          if(!is_child_question) {
            let logic = from_question_logic_value.logic[0];
            let logic_array = logic.rules || []; //ask Dmitriy would there ever be > 1 logic section in the array
            //let whole_entire_logic_for_this_question = from_question_logic_value.logic[0].logicSummaryText;
            for( let i = 0; i < logic_array.length; i++ ){
              let rule = logic_array[i];
              let order_id = rule.orderID;
              let goto_question_id = rule.rule_action.projQsId;
              let goto_question = nodes.find((elem) => { 
                if(goto_question_id==elem.qs_id){ return elem; } 
              });
              let goto_question_cd = goto_question.qs_code;
              let logic_broken = rule.rule_action.logicBroken;
              let logic_text = rule.text;
              //echo 'g.setEdge("QS1", "QS2", { label: }';
              //g.setEdge(from_question_key, goto_question_cd, { label: '<u>Rule'+ order_id +'</u>', hovertext:'A==B', labelType: 'html' });
              console.log(from_question_key +' to '+ goto_question_cd +'(aka '+goto_question_id+')');
              
              //g.setEdge({v: from_question_key, w: goto_question_cd, name: (from_question_key+'_'+goto_question_cd+'_Rule_'+ order_id) }, { name:  from_question_key+'_'+goto_question_cd+'_Rule_'+ order_id, label: "<u class='answer_hover_text'>Rule"+ order_id +"</u>", labelType: "html", lineInterpolate: 'basis' });
              //style: "stroke: #f66; stroke-width: 3px; stroke-dasharray: 5, 5;", labelStyle: "font-style: italic; text-decoration: underline;"
              g.setEdge({v: from_question_key, w: goto_question_cd, name: (from_question_key+'_'+goto_question_cd+'_Rule_'+ order_id) }, { name:  from_question_key+'_'+goto_question_cd+'_Rule_'+ order_id, label: "<div class='answer_hover_text' onmouseover='(function(){ $(\"#tooltip_template\").css(\"visibility\", \"visible\"); })()' onmouseout='(function(){  $(\"#tooltip_template\").css(\"visibility\", \"hidden\"); })()' onmousemove='(function(){ $(\"#tooltip_template\").html(\""+ from_question_key +' to '+ goto_question_cd + '<br/>' + escapeAnswerLogic(logic_text) +"\").css(\"top\", (event.pageY-10)+\"px\").css(\"left\",(event.pageX+10)+\"px\"); })()'>"+ convertModuleQuestionToQuestion(from_question_key)+'<br/>Rule#'+ order_id +'<br/>'+ convertModuleQuestionToQuestion(from_question_key, goto_question_cd) +"</div>", hovertext: (from_question_key +' to '+ goto_question_cd + '<br/>' + logic_text), labelType: "html", lineInterpolate: 'basis' });
              //g.setEdge(from_question_key, goto_question_cd,  { label: "<u class='answer_hover_text' onmouseover='(function(){ return $(\"#tooltip_template\").css(\"visibility\", \"visible\"); })()' onmouseout='(function(){ return $(\"#tooltip_template\").css(\"visibility\", \"hidden\"); })()' onmousemove='(function(){ $(\"#tooltip_template\").html(\""+ from_question_key +' to '+ goto_question_cd + '<br/>' + logic_text +"\").css(\"top\", (event.pageY-10)+\"px\").css(\"left\",(event.pageX+10)+\"px\"); })()'>Rule"+ order_id +"</u>", hovertext: (from_question_key +' to '+ goto_question_cd + '<br/>' + logic_text), labelType: "html" });
              //g.setEdge(from_question_key, goto_question_cd, { label: 'Rule'+ order_id, hovertext:'A==B' });
            }//for
          }//if
          
    });//Obj/func
    //loop over logic

    var svg = d3.select("svg"),
        inner = svg.select("g");

    // Set the rankdir
    g.graph().rankdir = 'TB';//'TB' (aka vertical) or 'LR' (aka horizontal)
    g.graph().nodesep = 50;

    // Set up zoom support
    var zoom = d3.behavior.zoom().on("zoom", function() {
          inner.attr("transform", "translate(" + d3.event.translate + ")" +
                                      "scale(" + d3.event.scale + ")");
          if(window.colorswitch=='#0000FF') {
            window.colorswitch = '#0000FE';
            //console.log('switch to: '+ window.colorswitch);
            setTimeout( function(){ $('.answer_hover_text').css('color',window.colorswitch); /*console.log('TIMEOUT');*/ }, 1000);
          } else {
            window.colorswitch = '#0000FF';
            //console.log('switch to: '+ window.colorswitch);
            setTimeout( function(){ $('.answer_hover_text').css('color',window.colorswitch); /*console.log('TIMEOUT');*/ }, 1000);
          }
        });
    svg.call(zoom);

    // Create the renderer
    var render = new dagreD3.render();


    // Run the renderer. This is what draws the final graph.
    render(inner, g);


    var tooltip = d3.select("body")
      .append("div")
      .attr('id', 'tooltip_template')
      .style("position", "absolute")
      .style("background-color", "white")
      .style("border", "solid")
      .style("border-width", "2px")
      .style("border-radius", "5px")  
      .style("padding", "5px")
      .style("z-index", "10")
      .style("visibility", "hidden")
      .text("Simple Tooltip...");
    
      document.getElementById('tooltip_template').onmouseover = function () {
          window.overpopup = true;
          //console.log(window.overpopup);
      }

      document.getElementById('tooltip_template').onmouseout = function () {
          window.overpopup = false;
          //console.log(window.overpopup);
      }
    
    inner.selectAll('g.node')
      .attr("data-hovertext", function(v) { 
        return g.node(v).hovertext
      })
      .on("mouseover", function(){ 
        tooltip.timestamp = new Date().getTime(); 
        console.log('new timestamp on popup'+ tooltip.timestamp ); 
        tooltip.style("visibility", "visible"); 
      })
      .on("mousemove", function(){ 
        tooltip.html( this.dataset.hovertext )   
          .style("top", (event.pageY-10)+"px")
          .style("left",(event.pageX+10)+"px");
      })
      .on("mouseout", function(){ tooltip.style("visibility", "hidden"); });
      /*
      .on("mouseout", function(){         
        let timestamp_when_left = tooltip.timestamp;
        console.log('mouseOut setTimeout called! timestamp is:'+ timestamp_when_left); 
        setTimeout( function(){           
          console.log('mouseout 5s later, '+ timestamp_when_left +'=?='+ tooltip.timestamp + ', window.overpopup='+ window.overpopup); 
          if(tooltip.style("visibility")!='hidden' && (timestamp_when_left == tooltip.timestamp) ) { 
            if(window.overpopup != true) {
              tooltip.style("visibility", "hidden");             
            } else { //when leave the popup, close it              
              //or do some interval check
              //tooltip.onmouseout = function () {
              //  window.overpopup = false;
              //  console.log(window.overpopup);
              //  //tooltip.style("visibility", "hidden");
              //}
            }
          }
          
      }, 5000 ) });
      */
      //.on("mouseout", function(){ console.log('mouseOut setTimeout called!'); setTimeout( function(){ if(tooltip.style("visibility")!='hidden') { tooltip.style("visibility", "hidden"); console.log('mouseOut setTimeout 2s later!'); } }, 5000 ) });

    inner.selectAll('g.node text').attr("pointer-events", "none"); //so the mouseout event doesn't occur when hover over the text inside the node

    inner.selectAll('g.edgePath')
    //inner.selectAll('path')
    .append('title').text('This is a line.');

    // Center the graph
    var initialScale = 0.75;
    zoom
      .translate([(svg.attr("width") - g.graph().width * initialScale) / 2, 20])
      .scale(initialScale)
      .event(svg);
    svg.attr('height', g.graph().height * initialScale + 40);
    window.colorswitch = '#0000FF';
    setTimeout( function(){ $('.answer_hover_text').css('color', window.colorswitch); /*console.log('TIMEOUT');*/ }, 5000);
  });
  
//});
};
</script>



</body>
</html>