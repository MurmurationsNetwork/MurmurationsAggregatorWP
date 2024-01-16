import ReactDiffViewer from 'react-diff-viewer-continued'
import PropTypes from 'prop-types'

export default function PopupBox({
  isPopupOpen,
  setIsPopupOpen,
  originalJson,
  setOriginalJson,
  modifiedJson,
  setModifiedJson
}) {
  const originalString = JSON.stringify(originalJson, null, 2)
  const modifiedString = JSON.stringify(modifiedJson, null, 2)

  const handlePopupClose = () => {
    setIsPopupOpen(false)
    setOriginalJson({})
    setModifiedJson({})
  }

  return (
    <div>
      {isPopupOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="w-1/2 rounded-lg border border-gray-300 bg-white p-4">
            <div className="mb-4 text-lg font-bold">See Updates</div>
            <hr />
            <div className="flex">
              <div className="max-h-80 overflow-y-auto">
                <ReactDiffViewer
                  oldValue={originalString}
                  newValue={modifiedString}
                  splitView={true}
                />
              </div>
            </div>
            <hr />
            <button
              className={`mx-2 my-1 max-w-fit rounded-full bg-red-500 px-4 py-2 text-base font-bold text-white hover:scale-110 hover:bg-red-400 active:scale-90 disabled:opacity-75`}
              onClick={() => handlePopupClose()}
            >
              Close
            </button>
          </div>
        </div>
      ) : null}
    </div>
  )
}

PopupBox.propTypes = {
  isPopupOpen: PropTypes.bool.isRequired,
  setIsPopupOpen: PropTypes.func.isRequired,
  originalJson: PropTypes.object.isRequired,
  setOriginalJson: PropTypes.func.isRequired,
  modifiedJson: PropTypes.object.isRequired,
  setModifiedJson: PropTypes.func.isRequired
}
