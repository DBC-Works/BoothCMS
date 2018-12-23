var vm = new Vue({
  el: '#app',
  data: {
    siteUrl: env.siteUrl,
    additionalContentStyle: {
      display: 'none'
    },
    moreButtonStyle: {
      display: 'none'
    },
    nextPageIndex: 1,
    moreButtonVisible: env.hasFollowing,
    additionalContents: []
  },
  mounted: function() {
    this.additionalContentStyle.display = this.moreButtonStyle.display = 'block';
    if (0 <= this.siteUrl.indexOf('http://example.com') || 0 <= window.location.origin.indexOf('localhost')) {
      this.correctHostname(this.siteUrl);
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
      this.correctElementHostname('link', this.siteUrl, modifiedUrl);
      this.correctElementHostname('a', this.siteUrl, modifiedUrl);
      this.siteUrl = modifiedUrl;
    },
    onClickMore: function(e) {
      var url = this.siteUrl + '/following/' + this.nextPageIndex + env.path;
      var self = this;
      axios.get(url)
      .then(function (response) {
        if (response.data.contents) {
          self.moreButtonVisible = response.data.hasFollowing;
          self.additionalContents = self.additionalContents.concat(response.data.contents);
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
