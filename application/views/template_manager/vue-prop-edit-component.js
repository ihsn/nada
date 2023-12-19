///prop edit componennt
Vue.component('prop-edit', {
    props:['value'],
    data: function () {    
        return {          
          field_data_types: [
            "string",
            "number",
            "integer",
            "boolean"
          ],
          field_display_types: [
            "text",
            "textarea",
            "date",
            "dropdown",
            "dropdown-custom"
          ]
        }
    },
    mounted: function(){
      if (!this.value.enum){
        //console.log("creating ENUM", this.value);
        //this.$set(this.prop, "enum", []);
      }
    },
    
    computed: {
        prop:{           
            get(){
              return this.value;
          },
          set(val){
              this.$emit('input:value', val);
          }
        },
        SimpleControlledVocabColumns: function(){
          return [
            {
              'type':'text',
              'key':'code',
              'title':'Code'
            },
            {
              'type':'text',
              'key':'label',
              'title':'Label'
            }
          ]
        },
        PropEnum: {
          get: function() {
            if (this.prop.enum && this.prop.enum.length>0 && typeof(this.prop.enum[0]) =='string')
            {
              let enum_list=[];
              this.prop.enum.forEach(function(item){
                enum_list.push({
                  'code':item,
                  'label':item
                });
              });
              Vue.set(this.prop,"enum",enum_list);
              return enum_list;
            }
            return this.prop.enum;
          },
          set: function(newValue) {
            this.prop.enum = newValue;
          }
        },
    },
    methods:{    
      isField: function(field_type){
        let field_types= [
          "text",
          "string",
          "number",
          "textarea",
          "dropdown",
          "date",
          "boolean",
          "integer"
        ];
        return field_types.includes(field_type);
      },
      isArrayField: function(prop){
        let array_types=['array', 'nested_array', 'simple_array'];

        if (array_types.includes(prop.type) && !prop.prop){
          return true;
        }

        return false;
      },
      EnumListUpdate: function(e) {
        if (Array.isArray(e)){
          this.$set(this.prop, "enum", e);
        }
        if (!this.prop.enum) {
          this.$set(this.prop, "enum", []);
        }
      },
      DefaultUpdate: function (e){
        if (Array.isArray(e)){
          this.$set(this.prop, "default", e);
        }
        if (!this.prop.default) {
          this.$set(this.prop, "default", []);
        }
      },
      RulesUpdate: function (e)
      {
        this.$set(this.prop, "rules", e);
      }
      
    },
    template: `<?php require_once 'vue-prop-edit-component-template.php';?>`    
});

