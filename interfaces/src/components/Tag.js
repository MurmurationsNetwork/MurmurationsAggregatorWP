class Tag extends React.Component {
  constructor(props) {
    super(props);
    this.handleClick = this.handleClick.bind(this);
  }

  handleClick(e) {
    alert(e);
    this.props.handleTagClick(e.target.id);
    //this.setState({temperature: e.target.value});
  }

  render() {
    return <span className="tag" id={this.props.tag}  onClick={this.handleClick}>{this.props.tag}</span>;
  }
}
