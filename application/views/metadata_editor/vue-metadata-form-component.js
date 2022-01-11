//Metadata Form ///////////////////////////////////////////////////
Vue.component('metadata-form', {
    props: ['title', 'items', 'depth', 'css_class','path', 'field'],
    data() {
        return {
            showChildren: true
        }
    },
    mounted:function(){
        //collapse all sections by default
        if (this.depth>0){
            this.toggleChildren();
        }
    },
    methods: {
        toggleChildren() {
            this.showChildren = !this.showChildren;
        },
        toggleNode(event){
            alert("event toggleNode");
        },
        showFieldError(field,error){
            //field_parts=field.split("-");
            //field_name=field_parts[field_parts.length-1];
            //return error.replace(field,field_name);
            return error.replace(field,'');
        }
    },
    computed: {
        toggleClasses() {
            return {
                'fa-angle-down': !this.showChildren,
                'fa-angle-up': this.showChildren
            }
        },
        hasChildrenClass() {
            return {
                'has-children': this.nodes
            }
        },
        formData () {
            return this.$deepModel('formData')
        }
    },
    template: `
        <div :class="'metadata-form ' + css_class + ' ' + 'field-type-' + field.type" >
            <div v-if="depth>0" class="label-wrapper" @click="toggleChildren">

                <div v-if="field.type=='section'" class="tree-node form-section" :class="hasChildrenClass">                            
                    {{ title }}
                    <span class="float-right section-toggle-icon"><i class="fas" :class="toggleClasses"></i></span>
                </div>

                <div v-if="field.type=='array'">
                    <div class="form-group form-field form-field-table">
                        <label :for="'field-' + normalizeClassID(path)">{{title}}</label>
                        <grid-component
                            :id="'field-' + normalizeClassID(path)" 
                            :value="formData[field.key]"                                         
                            :columns="field.props"
                            :path="field.key"
                            :field="field"
                            >
                        </grid-component>  
                    </div>    
                </div>

                <div v-if="field.type=='simple_array'">
                    <div class="form-group form-field form-field-table">
                        <label :for="'field-' + normalizeClassID(path)">{{title}}</label>
                        <simple-array-component
                            :id="'field-' + normalizeClassID(path)" 
                            :value="formData[field.key]"                            
                            :path="field.key"
                            :field="field"
                            >
                        </simple-array-component>  
                    </div>    
                </div>

                <div v-if="field.type=='nested_array'">
                    <label :for="'field-' + normalizeClassID(field.key)">{{title}}</label>
                    <nested-section 
                        :value="formData[field.key]"                                         
                        :columns="field.props"
                        :path="field.key">
                    </nested-section>  
                </div>

                <div v-if="field.type=='textarea'">

                    <div class="form-group form-field" :class="['field-' + field.key, field.class] ">
                        <label :for="'field-' + normalizeClassID(field.key)">{{title}}</label>
                        <textarea
                            v-model="formData[field.key]"        
                            class="form-control form-field-textarea" 
                            :id="'field-' + normalizeClassID(field.key)"                                     
                        ></textarea>
                        <small class="help-text form-text text-muted">{{field.help_text}}</small>                            
                    </div>

                </div> 


                <div v-if="field.type=='dropdown'">

                    <div class="form-group form-field" :class="['field-' + field.key, field.class] ">
                        <label :for="'field-' + normalizeClassID(field.key)">{{title}}</label>
                        <select 
                            v-model="formData[field.key]" 
                            class="form-control form-field-dropdown"
                            :id="'field-' + normalizeClassID(field.key)" 
                        >
                            <option value="">Select</option>
                            <option v-for="(option_key,option_value) in field.enum" v-bind:value="option_value">
                                {{ option_key }}
                            </option>
                        </select>
                        <small class="help-text form-text text-muted">{{formData[field.key]}}</small>
                        <small class="help-text form-text text-muted">{{field.help_text}}</small>
                    </div>

                </div>  

                <div v-if="field.type=='text' || field.type=='string' ">

                    <div class="form-group form-field" :class="['field-' + field.key, field.class] ">
                        <label :for="'field-' + normalizeClassID(field.key)">
                            {{title}} 
                            <span class="small" v-if="field.help_text" role="button" data-toggle="collapse" :data-target="'#field-toggle-' + normalizeClassID(field.key)" ><i class="far fa-question-circle"></i></span>
                            <span v-if="field.required==true" class="required-label"> * </span>
                        </label>
                        
                        <validation-provider 
                            :rules="field.rules" 
                            :debounce=500
                            v-slot="{ errors }"                            
                            :name="field.title"
                            >
                        <input type="text"
                            v-model="formData[field.key]"
                            class="form-control"                            
                            :id="'field-' + normalizeClassID(field.key)"                                     
                        >
                        <span v-if="errors[0]" class="error">{{errors[0]}}</span>
                      </validation-provider>

                        <!--<input type="text"
                            v-model="formData[field.key]"
                            class="form-control" 
                            :id="'field-' + normalizeClassID(field.key)"                                     
                        >-->
                        <small :id="'field-toggle-' + normalizeClassID(field.key)" class="collapse help-text form-text text-muted">{{field.help_text}}</small>                            
                    </div>

                </div>  


                
            </div>
            <metadata-form
                v-show="showChildren" 
                v-for="item in items" 
                    :items="item.items" 
                    :title="item.title"
                    :depth="depth + 1"
                    :path="item.key"
                    :field="item"
                    :css_class="'lvl-' + depth" 
            >
            </metadata-form>
        </div>
    `
})