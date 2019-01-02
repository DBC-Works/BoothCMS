var vm = new Vue({
  el: '#app',
  data: {
    env: env,
    page: page ? page : {},

    followingContentWrapper: {
      display: 'none'
    },
    requestFollowing: {
      display: 'none'
    },

    nextPageIndex: 1,
    followingContents: []
  },
  mounted: function() {
    this.followingContentWrapper.display = this.requestFollowing.display = 'block';
    if (this.env.asDevelop) {
      this.correctHostname(this.env.siteUrl);
    }
  },
  methods: {
    correctAttrHostname: function(element, attrName, target, modified) {
      element.setAttribute(attrName, element.getAttribute(attrName).replace(target, modified));
    },
    correctElementHostname: function(elementName, target, modified) {
      var elements = document.getElementsByTagName(elementName);
      for (var index = 0; index !== elements.length; ++index) {
        this.correctAttrHostname(elements[index], 'href', target, modified);
      }
    },
    correctHostname: function(target) {
      var m = new RegExp('^https?:\/\/[^\/]+(.*)').exec(target);
      var modifiedUrl = window.location.origin + m[1];
      this.correctElementHostname('link', this.env.siteUrl, modifiedUrl);
      this.correctElementHostname('a', this.env.siteUrl, modifiedUrl);
      this.env.siteUrl = modifiedUrl;
    },
    onClickRequestFollowing: function(e) {
      var url = this.env.siteUrl + '/following/' + this.nextPageIndex + this.env.path;
      var self = this;
      axios.get(url)
      .then(function (response) {
        if (response.data.contents) {
          self.env.hasFollowing = response.data.hasFollowing;
          self.followingContents = self.followingContents.concat(response.data.contents);
          ++(self.nextPageIndex);
        } else {
          console.error(response.data);
          window.alert('Invalid content:\n' + response.data);
        }
      })
      .catch(function (error) {
        console.error(error);
        window.alert(error);
      });
    }
  }
});
