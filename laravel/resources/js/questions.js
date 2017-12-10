$ = require('wetfish-basic');
Vue = require('Vue');

$(document).ready(function()
{
    $('.answer-type').on('change', function()
    {
        if($(this).value() == 'dropdown')
        {
            $('.question-options').removeClass('hidden');
        }
        else
        {
            $('.question-options').addClass('hidden');
        }
    });

    // Useless button to make users feel better about ajax autosave
    $('.submit-answer button').on('click', function()
    {
        var status = $(this).parents('.submit-answer').find('.status');

        // Make it look like this button actually does something
        setTimeout(function()
        {
            status.removeClass('hidden');
        }, 300);
    });

    //create vue form component for any vue-form class on the page
    var vueBudget = Array.from( document.querySelectorAll('.vue-budget') );

    if(vueBudget.length > 0){
        //make a new vue component for each found vue budget class
        vueBudget.forEach(function(vueBudgetEl){

            let vm = new Vue({

                el:vueBudgetEl,

                data :function() {
                    return {
                        outputString: "",
                        fields:[{cost:3, description:"whaaatt?"}]
                    }
                },
                methods:{
                    inputChanged:function(){
                        console.log('updating output string');
                        let newOutput = JSON.stringify(this.fields.map(function(item){return item.cost + ":" + item.description}))
                        this.$set(this, 'outputString' , newOutput.toString());
                        console.log(this.outputString);
                    },
                    addField:function(){
                        console.log('clicked');
                        this.fields.push({cost:0,description:""});
                    },
                    removeField:function(index){
                        this.fields.splice(index, 1);
                    }
                }
            });

        })
    }
});
