module.exports =
{
    watch: true,

    entry:
    {
        main: './resources/js/main.js',
    },

    output:
    {
        filename: './bundle.js'
    },

    resolve: {
	    alias: {
	      'Vue': 'vue/dist/vue.js'
	    }
  	}
};
